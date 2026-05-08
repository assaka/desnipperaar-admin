<?php

namespace App\Http\Controllers;

use App\Mail\GroupDealApproved;
use App\Mail\GroupDealCancelled;
use App\Mail\GroupDealRejected;
use App\Models\GroupDeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class GroupDealController extends Controller
{
    public function index()
    {
        $deals = GroupDeal::with('organizerParticipant')
            ->withCount('participants')
            ->withSum('participants as participants_box_sum', 'box_count')
            ->withSum('participants as participants_container_sum', 'container_count')
            ->orderByDesc('id')
            ->paginate(25);
        return view('group-deals.index', compact('deals'));
    }

    public function show(GroupDeal $groupDeal)
    {
        $groupDeal->load(['participants', 'organizerParticipant']);
        return view('group-deals.show', ['deal' => $groupDeal]);
    }

    public function approve(GroupDeal $groupDeal)
    {
        abort_unless($groupDeal->status === GroupDeal::STATUS_DRAFT, 422);
        $groupDeal->update([
            'status'      => GroupDeal::STATUS_OPEN,
            'approved_at' => now(),
        ]);
        $organizer = $groupDeal->organizerParticipant;
        if ($organizer) {
            try {
                // "Your proposal is live, share it" mail.
                Mail::to($organizer->customer_email)->send(new GroupDealApproved($groupDeal));
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                // Same welcome confirmation joiners get (locked price + perk badge),
                // since the organizer is also a participant — they shouldn't have
                // to wait for the cron close to see their own locked numbers.
                Mail::to($organizer->customer_email)
                    ->send(new \App\Mail\GroupDealJoined($groupDeal, $organizer));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return back()->with('status', 'Groepsdeal goedgekeurd en gepubliceerd.');
    }

    public function reject(Request $request, GroupDeal $groupDeal)
    {
        abort_unless($groupDeal->status === GroupDeal::STATUS_DRAFT, 422);
        $data = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);
        $groupDeal->update([
            'status'              => GroupDeal::STATUS_REJECTED,
            'cancellation_reason' => $data['cancellation_reason'],
        ]);
        if ($groupDeal->organizerParticipant) {
            try {
                Mail::to($groupDeal->organizerParticipant->customer_email)
                    ->send(new GroupDealRejected($groupDeal));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return back()->with('status', 'Groepsdeal afgewezen.');
    }

    public function cancel(Request $request, GroupDeal $groupDeal)
    {
        abort_unless(in_array($groupDeal->status, [GroupDeal::STATUS_OPEN, GroupDeal::STATUS_DRAFT], true), 422);
        $data = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);
        $groupDeal->update([
            'status'              => GroupDeal::STATUS_CANCELLED,
            'cancelled_at'        => now(),
            'cancellation_reason' => $data['cancellation_reason'],
        ]);
        try {
            Mail::send(new GroupDealCancelled($groupDeal));
        } catch (\Throwable $e) {
            report($e);
        }
        return back()->with('status', 'Groepsdeal geannuleerd.');
    }

    public function manualClose(GroupDeal $groupDeal)
    {
        abort_unless($groupDeal->status === GroupDeal::STATUS_OPEN, 422);

        // --deal=ID bypasses the cron's pickup_date filter so admin can close
        // a deal early (e.g. target reached, no need to wait for the cutoff).
        $exit = \Illuminate\Support\Facades\Artisan::call('group-deals:close', [
            '--deal' => $groupDeal->id,
        ]);
        $output = trim(\Illuminate\Support\Facades\Artisan::output());

        if ($exit !== 0) {
            return back()->with('status', "Sluiten mislukt: {$output}");
        }

        $groupDeal->refresh();
        $orderCount = $groupDeal->participants()->whereNotNull('order_id')->count();
        return back()->with('status', "Deal gesloten. {$orderCount} order(s) aangemaakt en bevestigingsmails zijn verstuurd.");
    }
}
