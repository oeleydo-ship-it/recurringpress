<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function create()
    {
        if (auth()->user()->company_id) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Company/Create');
    }

    public function store(Request $request)
    {
        // Only users without a company may create one (previously any member could move
        // themselves into a brand-new company by posting here).
        if (auth()->user()->company_id) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email:rfc,filter|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? auth()->user()->email,
                'phone' => $validated['phone'] ?? null,
                'currency' => 'USD',
                'invoice_prefix' => 'INV-',
                'invoice_start_number' => 1,
                'payment_terms' => 30,
            ]);

            CompanySetting::create([
                'company_id' => $company->id,
            ]);

            auth()->user()->update([
                'company_id' => $company->id,
                'role' => 'owner',
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', 'Company created successfully.');
    }
}
