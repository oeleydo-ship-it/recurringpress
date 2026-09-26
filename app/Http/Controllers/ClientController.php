<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

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

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
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

        $validated = $request->validate([
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
        ]);

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

        $stats = [
            'total_invoices' => $client->invoices()->count(),
            'total_billed' => $client->invoices()->sum('total'),
            'total_paid' => $client->invoices()->where('status', 'paid')->sum('total'),
            'outstanding' => $client->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total'),
        ];

        return view('clients.show', compact('client', 'stats'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
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
        ]);

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
}
