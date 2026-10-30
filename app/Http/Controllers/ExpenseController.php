<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Services\TenantNotifier;
use App\Support\CustomFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /** Receipts are private files, served only through the authenticated receipt route. */
    private const RECEIPT_DISK = 'local';

    public function index(Request $request)
    {
        $query = Expense::with(['invoice', 'user']);

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

        // Totals must be computed before paginate(), which adds LIMIT/OFFSET to the builder
        // (previously every page after the first showed 0 totals).
        $totals = [
            'amount' => (float) (clone $query)->sum('amount'),
            'tax' => (float) (clone $query)->sum('tax_amount'),
            'count' => (clone $query)->count(),
        ];

        $byCategory = (clone $query)->select('category', DB::raw('SUM(amount) as category_total'))
            ->groupBy('category')->orderByDesc('category_total')->pluck('category_total', 'category')
            ->map(fn ($v) => (float) $v);

        $expenses = $query->orderBy('expense_date', 'desc')->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses->through(fn (Expense $e) => $this->row($e)),
            'filters' => $request->only('search', 'category', 'from', 'to'),
            'categories' => Expense::categories(),
            'totals' => $totals,
            'byCategory' => $byCategory,
            'customColumns' => CustomFields::listColumns('expense'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Expenses/Form', ['expense' => null, ...$this->formOptions()]);
    }

    public function store(Request $request)
    {
        [$validated, $custom] = CustomFields::validateWith($request, 'expense', fn () => $this->validated($request));
        $validated['custom_values'] = $custom ?: null;

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', self::RECEIPT_DISK);
        }
        unset($validated['receipt']);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['user_id'] = auth()->id();
        $validated['is_billable'] = $request->boolean('is_billable');
        $validated['is_reimbursed'] = $request->boolean('is_reimbursed');
        $validated['currency'] = auth()->user()->company->currency ?? 'USD';

        $expense = DB::transaction(function () use (&$validated) {
            $validated['expense_number'] = $this->nextExpenseNumber();

            return Expense::create($validated);
        });

        TenantNotifier::notify(
            $expense->company_id,
            'expense_added',
            "Expense added: {$expense->title}",
            TenantNotifier::money($expense->amount, $expense->currency)
                . ($expense->vendor ? " · {$expense->vendor}" : '')
                . TenantNotifier::byActor() . '.',
            TenantNotifier::path('expenses.show', $expense->id),
        );

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['invoice', 'user']);

        return Inertia::render('Expenses/Show', [
            'expense' => [
                ...$this->row($expense),
                'description' => $expense->description,
                'reference' => $expense->reference,
                'created_by' => $expense->user?->name,
                'created_at' => $expense->created_at?->toIso8601String(),
                'receipt_url' => $expense->receipt_path ? route('expenses.receipt', $expense) : null,
                'receipt_is_pdf' => $expense->receipt_path && str_ends_with(strtolower($expense->receipt_path), '.pdf'),
            ],
            'paymentMethods' => Expense::paymentMethods(),
            'customFields' => CustomFields::forForm('expense'),
        ]);
    }

    public function edit(Expense $expense)
    {
        return Inertia::render('Expenses/Form', [
            'expense' => [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'title' => $expense->title,
                'category' => $expense->category,
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'tax_amount' => (float) $expense->tax_amount,
                'vendor' => $expense->vendor,
                'payment_method' => $expense->payment_method,
                'reference' => $expense->reference,
                'expense_date' => $expense->expense_date?->toDateString(),
                'invoice_id' => $expense->invoice_id,
                'is_billable' => (bool) $expense->is_billable,
                'is_reimbursed' => (bool) $expense->is_reimbursed,
                'has_receipt' => (bool) $expense->receipt_path,
                'receipt_url' => $expense->receipt_path ? route('expenses.receipt', $expense) : null,
                'custom_values' => $expense->custom_values,
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        [$validated, $custom] = CustomFields::validateWith($request, 'expense', fn () => $this->validated($request), $expense->custom_values);
        $validated['custom_values'] = $custom ?: null;

        if ($request->hasFile('receipt')) {
            $this->deleteReceipt($expense);
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', self::RECEIPT_DISK);
        } elseif ($request->boolean('remove_receipt')) {
            $this->deleteReceipt($expense);
            $validated['receipt_path'] = null;
        }
        unset($validated['receipt'], $validated['remove_receipt']);

        $validated['is_billable'] = $request->boolean('is_billable');
        $validated['is_reimbursed'] = $request->boolean('is_reimbursed');

        $expense->update($validated);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $this->deleteReceipt($expense);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    /** Stream the receipt file to an authenticated user of the same company. */
    public function receipt(Expense $expense)
    {
        abort_unless($expense->receipt_path, 404);

        foreach ([self::RECEIPT_DISK, 'public'] as $disk) { // 'public' = files uploaded before this change
            if (Storage::disk($disk)->exists($expense->receipt_path)) {
                return Storage::disk($disk)->response($expense->receipt_path, null, [
                    'Cache-Control' => 'private, max-age=0, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(Expense::categories()))],
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'tax_amount' => 'nullable|numeric|min:0|max:999999999',
            'vendor' => 'nullable|string|max:255',
            'payment_method' => ['required', Rule::in(array_keys(Expense::paymentMethods()))],
            'reference' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            // Scoped to this company (was exists:invoices,id — could link another tenant's invoice).
            'invoice_id' => ['nullable', Rule::exists('invoices', 'id')->where('company_id', auth()->user()->company_id)],
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_receipt' => 'nullable|boolean',
            'is_billable' => 'nullable|boolean',
            'is_reimbursed' => 'nullable|boolean',
        ]);

        // A blank "Tax paid" arrives as null, but expenses.tax_amount is NOT NULL.
        $validated['tax_amount'] ??= 0;

        return $validated;
    }

    /**
     * Next EXP-###### number. Uses the highest existing number under a company-row lock;
     * the old count()+1 approach reused numbers after a deletion.
     */
    protected function nextExpenseNumber(): string
    {
        $companyId = auth()->user()->company_id;
        DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();

        $max = Expense::withoutGlobalScopes()->where('company_id', $companyId)
            ->where('expense_number', 'like', 'EXP-%')
            ->pluck('expense_number')
            ->map(fn ($n) => (int) preg_replace('/\D/', '', $n))
            ->max() ?? 0;

        return 'EXP-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    private function deleteReceipt(Expense $expense): void
    {
        if ($expense->receipt_path) {
            Storage::disk(self::RECEIPT_DISK)->delete($expense->receipt_path);
            Storage::disk('public')->delete($expense->receipt_path);
        }
    }

    private function formOptions(): array
    {
        return [
            'categories' => Expense::categories(),
            'paymentMethods' => Expense::paymentMethods(),
            'customFields' => CustomFields::forForm('expense'),
            'invoices' => Invoice::with('client:id,name,company_name,client_type')->orderByDesc('id')->limit(200)->get(['id', 'invoice_number', 'client_id'])
                ->map(fn ($i) => ['id' => $i->id, 'label' => $i->invoice_number, 'sublabel' => $i->client?->bill_to_name]),
        ];
    }

    private function row(Expense $e): array
    {
        return [
            'id' => $e->id,
            'expense_number' => $e->expense_number,
            'title' => $e->title,
            'category' => $e->category,
            'category_label' => Expense::categories()[$e->category] ?? ucfirst((string) $e->category),
            'vendor' => $e->vendor,
            'amount' => (float) $e->amount,
            'tax_amount' => (float) $e->tax_amount,
            'total' => (float) $e->amount + (float) $e->tax_amount,
            'payment_method' => $e->payment_method,
            'expense_date' => $e->expense_date?->toDateString(),
            'is_billable' => (bool) $e->is_billable,
            'is_reimbursed' => (bool) $e->is_reimbursed,
            'has_receipt' => (bool) $e->receipt_path,
            'invoice_id' => $e->invoice_id,
            'invoice_number' => $e->invoice?->invoice_number,
            'custom_values' => $e->custom_values,
        ];
    }
}
