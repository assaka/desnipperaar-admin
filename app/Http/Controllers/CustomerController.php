<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $customers = Customer::query()
            ->when($q, function ($qb) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $qb->where(function ($sub) use ($like) {
                    $sub->where('name',    'ilike', $like)
                        ->orWhere('company', 'ilike', $like)
                        ->orWhere('email',   'ilike', $like)
                        ->orWhere('postcode','ilike', $like);
                });
            })
            ->withCount('orders')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);
        $customer = Customer::create($data);
        return redirect()->route('customers.show', $customer);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => fn($q) => $q->orderByDesc('id')]);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateCustomer($request, $customer->id));
        return redirect()->route('customers.show', $customer);
    }

    /** JSON autocomplete for the order form. */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $hits = Customer::query()
            ->where(function ($sub) use ($like) {
                $sub->where('name','ilike',$like)
                    ->orWhere('company','ilike',$like)
                    ->orWhere('email','ilike',$like);
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id','name','company','email','phone','address','postcode','city','reference']);

        return response()->json($hits);
    }

    private function validateCustomer(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'      => 'required|string|max:255',
            'company'   => 'nullable|string|max:255',
            'email'     => 'required|email|unique:customers,email' . ($ignoreId ? ",$ignoreId" : ''),
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'postcode'  => ['nullable','string','max:10','regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'city'      => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:100',
            'branche'   => 'nullable|string|max:100',
            'notes'     => 'nullable|string|max:5000',
        ], [
            'postcode.regex' => 'Postcode moet NL-formaat zijn (bv. 1034 AB).',
        ]);
    }
}
