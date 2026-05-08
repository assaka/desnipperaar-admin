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
        try {
            Mail::to($groupDeal->organizerParticipant->customer_email)
                ->send(new GroupDealApproved($groupDeal));
        } catch (\Throwable $e) {
            report($e);
        }
        return back()->with('status', 'Groepdeal goedgekeurd en gepubliceerd.');
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
        return back()->with('status', 'Groepdeal afgewezen.');
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
        return back()->with('status', 'Groepdeal geannuleerd.');
    }

    public function manualClose(GroupDeal $groupDeal)
    {
        abort_unless($groupDeal->status === GroupDeal::STATUS_OPEN, 422);
        \Illuminate\Support\Facades\Artisan::call('group-deals:close');
        return back()->with('status', 'Sluiten getriggerd; orders worden aangemaakt.');
    }
}
