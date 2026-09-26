<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['invoice', 'user'])->orderBy('expense_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }

        $expenses = $query->paginate(15)->withQueryString();

        $totalAmount = (clone $query)->sum('amount');
        $totalTax = (clone $query)->sum('tax_amount');
        $categories = Expense::categories();

        return view('expenses.index', compact('expenses', 'categories', 'totalAmount', 'totalTax'));
    }

    public function create()
    {
        $categories = Expense::categories();
        $paymentMethods = Expense::paymentMethods();
        $invoices = Invoice::orderByDesc('id')->limit(100)->get(['id', 'invoice_number']);

        return view('expenses.create', compact('categories', 'paymentMethods', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $validated['company_id'] = auth()->user()->company_id;
        $validated['user_id'] = auth()->id();
        $validated['is_billable'] = $request->boolean('is_billable');
        $validated['is_reimbursed'] = $request->boolean('is_reimbursed');
        $validated['currency'] = auth()->user()->company->currency ?? 'USD';
        $validated['expense_number'] = $this->nextExpenseNumber();

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['invoice', 'user']);
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $categories = Expense::categories();
        $paymentMethods = Expense::paymentMethods();
        $invoices = Invoice::orderByDesc('id')->limit(100)->get(['id', 'invoice_number']);

        return view('expenses.edit', compact('expense', 'categories', 'paymentMethods', 'invoices'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $validated['is_billable'] = $request->boolean('is_billable');
        $validated['is_reimbursed'] = $request->boolean('is_reimbursed');

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'payment_method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'invoice_id' => 'nullable|exists:invoices,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'is_billable' => 'nullable|boolean',
            'is_reimbursed' => 'nullable|boolean',
        ]);
    }

    protected function nextExpenseNumber(): string
    {
        $companyId = auth()->user()->company_id;
        $count = Expense::withoutGlobalScopes()->where('company_id', $companyId)->count();
        return 'EXP-' . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
