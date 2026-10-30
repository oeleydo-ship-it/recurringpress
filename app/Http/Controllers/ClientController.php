<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\CustomFields;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $clients = $query->withCount('invoices')
            ->withSum([
                'invoices as total_billed' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                }
            ], 'total')
            ->latest()
            ->paginate(15);

        return Inertia::render('Clients/Index', [
            'clients' => $clients->through(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'display_name' => $c->bill_to_name,
                'client_type' => $c->client_type,
                'company_name' => $c->company_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'city' => $c->city,
                'country' => $c->country,
                'is_active' => (bool) ($c->is_active ?? true),
                'invoices_count' => $c->invoices_count,
                'total_billed' => (float) $c->total_billed,
                'custom_values' => $c->custom_values,
            ]),
            'filters' => $request->only('search', 'status'),
            'customColumns' => CustomFields::listColumns('client'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Form', ['client' => null, 'customFields' => CustomFields::forForm('client')]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        if ($company->hasReachedLimit('clients')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'You have reached the client limit for your current plan. Please upgrade to add more clients.'], 422);
            }
            return back()->with('error', 'You have reached the client limit for your current plan. Please upgrade to add more clients.');
        }

        $request->merge(['client_type' => $request->input('client_type', 'individual')]);

        [$validated, $custom] = CustomFields::validateWith($request, 'client', fn () => $request->validate([
            'name' => 'required|string|max:255',
            'client_type' => 'required|in:individual,company',
            'company_name' => 'required_if:client_type,company|nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]));
        $validated['custom_values'] = $custom ?: null;

        if ($validated['client_type'] === 'individual') {
            $validated['company_name'] = null;
        }

        $validated['company_id'] = auth()->user()->company_id;

        $client = Client::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['id' => $client->id, 'name' => $client->name, 'email' => $client->email, 'select_label' => $client->select_label]);
        }

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load([
            'invoices' => function ($query) {
                $query->latest()->take(10);
            }
        ]);

        // Paid / outstanding use the per-invoice paid_amount so partial payments count.
        $stats = [
            'total_invoices' => $client->invoices()->count(),
            'total_billed' => (float) $client->invoices()->where('status', '!=', 'cancelled')->sum('total'),
            'total_paid' => (float) $client->invoices()->sum('paid_amount'),
            'outstanding' => (float) $client->invoices()->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
                ->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
        ];

        return Inertia::render('Clients/Show', [
            'client' => $this->clientPayload($client) + [
                'bill_to_name' => $client->bill_to_name,
                'full_address' => $client->full_address,
                'created_at' => $client->created_at?->toDateString(),
            ],
            'invoices' => $client->invoices->map(fn ($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'issue_date' => $i->issue_date?->toDateString(),
                'due_date' => $i->due_date?->toDateString(),
                'total' => (float) $i->total,
                'paid_amount' => (float) $i->paid_amount,
                'status' => $i->status,
            ]),
            'stats' => $stats,
            'customFields' => CustomFields::forForm('client'),
        ]);
    }

    public function edit(Client $client)
    {
        return Inertia::render('Clients/Form', ['client' => $this->clientPayload($client), 'customFields' => CustomFields::forForm('client')]);
    }

    public function update(Request $request, Client $client)
    {
        [$validated, $custom] = CustomFields::validateWith($request, 'client', fn () => $request->validate([
            'name' => 'required|string|max:255',
            'client_type' => 'required|in:individual,company',
            'company_name' => 'required_if:client_type,company|nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]), $client->custom_values);
        $validated['custom_values'] = $custom ?: null;

        if ($validated['client_type'] === 'individual') {
            $validated['company_name'] = null;
        }

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists()) {
            return back()->with('error', 'Cannot delete client with existing invoices.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
    private function clientPayload(Client $client): array
    {
        return $client->only([
            'id', 'name', 'client_type', 'company_name', 'email', 'phone', 'tax_number',
            'address', 'city', 'state', 'postal_code', 'country', 'notes',
        ]) + ['is_active' => (bool) ($client->is_active ?? true), 'custom_values' => $client->custom_values];
    }
}
