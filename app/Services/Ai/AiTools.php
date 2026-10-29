<?php

namespace App\Services\Ai;

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The tools the header chat can call on the signed-in user's company.
 *
 * Read tools query this company's records only (every query is scoped by company_id
 * explicitly, not just by the models' auth-based global scopes). Write tools never write:
 * they check the request and park it as a pending action under a random single-use token
 * in the cache, bound to the user and company. The record is only created when that user
 * clicks Confirm in the chat (AiController::confirm), through the same controller store()
 * the normal create forms post to, so numbering, validation, totals and plan limits match.
 */
class AiTools
{
    public const READ = ['search_invoices', 'get_invoice', 'search_clients', 'search_expenses', 'search_quotations', 'search_products', 'search_payments', 'business_summary'];

    public const WRITE = ['draft_invoice', 'draft_quotation', 'draft_expense'];

    private const LIMIT = 25;

    private const ACTION_TTL_MINUTES = 30;

    private const OPEN = ['sent', 'viewed', 'partial', 'overdue'];

    /** @var array<int, array{token: string, type: string, title: string, lines: string[], total: ?string}> actions proposed in this turn */
    public array $actions = [];

    /** @var array<string, string> record label => in-app path, for links under the answer */
    public array $links = [];

    private int $cid;

    private string $currency;

    public function __construct(private Company $company, private User $user)
    {
        $this->cid = $company->id;
        $this->currency = $company->currency ?? 'USD';
    }

    /** Tool descriptions for the system prompt. */
    public static function catalog(): string
    {
        $statuses = implode('|', array_keys(Invoice::statuses()));
        $categories = implode('|', array_keys(Expense::categories()));
        $methods = implode('|', array_keys(Expense::paymentMethods()));
        $item = '{"name": string, "description"?: string, "quantity": number, "price"?: number, "tax_rate"?: number (percent), "unit"?: string, "product_id"?: int}';

        return implode("\n", [
            '- search_invoices {"status"?: "unpaid"|"overdue"|' . $statuses . ', "client"?: name or id, "search"?: invoice number or reference, "date_from"?: "YYYY-MM-DD", "date_to"?: "YYYY-MM-DD" (issue date), "limit"?: int} — invoices with balance; "unpaid" = sent, viewed, partial or overdue.',
            '- get_invoice {"number": string} — one invoice with its line items and payments.',
            '- search_clients {"search"?: string, "sort"?: "top"|"owed"|"name"|"recent", "date_from"?, "date_to"?, "limit"?} — clients with invoiced, paid and owed totals. "top" sorts by amount invoiced (in the date range if given).',
            '- search_expenses {"search"?: string, "category"?: ' . $categories . ', "date_from"?, "date_to"?, "limit"?} — expenses, with the total and totals by category.',
            '- search_quotations {"status"?: draft|sent|accepted|rejected|expired|converted, "client"?, "search"?, "date_from"?, "date_to"?, "limit"?}',
            '- search_products {"search"?: string, "limit"?} — products and services with price, unit and tax rate.',
            '- search_payments {"client"?, "date_from"?, "date_to"?, "limit"?} — payments received.',
            '- business_summary {"date_from"?: "YYYY-MM-DD", "date_to"?: "YYYY-MM-DD"} — invoiced, collected, expenses and profit for the period (default: this month), plus current outstanding and overdue balances.',
            '- draft_invoice {"client": name or id, "items": [' . $item . ', ...], "issue_date"?, "due_date"?, "reference"?, "notes"?, "discount"?: number, "discount_type"?: "fixed"|"percentage"} — prepares a DRAFT invoice for the user to confirm.',
            '- draft_quotation {"client": name or id, "items": [...same as invoice], "issue_date"?, "valid_until"?, "reference"?, "notes"?, "discount"?, "discount_type"?} — prepares a DRAFT quotation for the user to confirm.',
            '- draft_expense {"title": string, "amount": number, "category": ' . $categories . ', "payment_method"?: ' . $methods . ', "expense_date"?, "vendor"?, "tax_amount"?, "reference"?, "description"?} — prepares an expense for the user to confirm.',
        ]);
    }

    /**
     * Run one tool call. Returns data for the model; errors come back as ['error' => ...]
     * so the model can correct itself.
     */
    public function run(string $name, array $args): array
    {
        try {
            return match ($name) {
                'search_invoices' => $this->searchInvoices($args),
                'get_invoice' => $this->getInvoice($args),
                'search_clients' => $this->searchClients($args),
                'search_expenses' => $this->searchExpenses($args),
                'search_quotations' => $this->searchQuotations($args),
                'search_products' => $this->searchProducts($args),
                'search_payments' => $this->searchPayments($args),
                'business_summary' => $this->businessSummary($args),
                'draft_invoice' => $this->draftDocument('invoice', $args),
                'draft_quotation' => $this->draftDocument('quotation', $args),
                'draft_expense' => $this->draftExpense($args),
                default => ['error' => "Unknown tool {$name}."],
            };
        } catch (ToolError $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /* ------------------------------- Reads ------------------------------- */

    private function searchInvoices(array $a): array
    {
        $q = Invoice::withoutGlobalScopes()->where('invoices.company_id', $this->cid)
            ->with(['client' => fn ($c) => $c->withoutGlobalScopes()]);

        match ($status = $a['status'] ?? null) {
            null, '', 'any', 'all' => null,
            'unpaid' => $q->whereIn('status', self::OPEN),
            'overdue' => $q->whereIn('status', self::OPEN)->whereDate('due_date', '<', today()),
            default => array_key_exists($status, Invoice::statuses()) ? $q->where('status', $status) : throw new ToolError("Unknown status {$status}."),
        };

        if ($client = $this->clientFilter($a)) {
            $q->where('client_id', $client->id);
        }
        if (filled($a['search'] ?? null)) {
            $s = (string) $a['search'];
            $q->where(fn ($w) => $w->where('invoice_number', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%"));
        }
        $this->dateRange($q, 'issue_date', $a);

        $total = (clone $q)->count();
        $sums = (clone $q)->selectRaw('SUM(total) as t, SUM(total - COALESCE(paid_amount, 0)) as b')->first();
        $rows = $q->orderByDesc('issue_date')->orderByDesc('id')->limit($this->limit($a))->get();

        return [
            'currency' => $this->currency,
            'matching_count' => $total,
            'matching_total' => round((float) $sums->t, 2),
            'matching_balance' => round((float) $sums->b, 2),
            'invoices' => $rows->map(fn (Invoice $i) => $this->invoiceRow($i))->all(),
        ];
    }

    private function getInvoice(array $a): array
    {
        $number = trim((string) ($a['number'] ?? ''));
        $invoice = Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->where('invoice_number', $number)
            ->with(['client' => fn ($c) => $c->withoutGlobalScopes(), 'items', 'payments' => fn ($p) => $p->withoutGlobalScopes()])
            ->first() ?? throw new ToolError("No invoice numbered {$number}.");

        return [
            ...$this->invoiceRow($invoice),
            'currency' => $this->currency,
            'reference' => $invoice->reference,
            'subtotal' => round((float) $invoice->subtotal, 2),
            'tax' => round((float) $invoice->tax_amount, 2),
            'discount' => round((float) $invoice->discount_amount, 2) . ($invoice->discount_type === 'percentage' ? '%' : ''),
            'client_email' => $invoice->client?->email,
            'items' => $invoice->items->map(fn ($i) => [
                'name' => $i->name, 'quantity' => (float) $i->quantity, 'unit' => $i->unit,
                'price' => (float) $i->price, 'tax_rate' => (float) $i->tax_rate,
            ])->all(),
            'payments' => $invoice->payments->map(fn ($p) => [
                'number' => $p->payment_number, 'date' => $p->payment_date?->toDateString(),
                'amount' => (float) $p->amount, 'method' => $p->payment_method, 'status' => $p->status,
            ])->all(),
        ];
    }

    private function searchClients(array $a): array
    {
        $q = Client::withoutGlobalScopes()->where('clients.company_id', $this->cid);

        if (filled($a['search'] ?? null)) {
            $s = (string) $a['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('company_name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }

        // Per-client figures from this company's invoices (drafts and cancelled ones excluded).
        $inv = Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->whereNotIn('status', ['draft', 'cancelled']);
        $this->dateRange($inv, 'issue_date', $a);
        $stats = $inv->groupBy('client_id')->selectRaw('client_id, COUNT(*) as n, SUM(total) as invoiced, SUM(COALESCE(paid_amount, 0)) as paid')
            ->get()->keyBy('client_id');
        $owed = Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->whereIn('status', self::OPEN)
            ->groupBy('client_id')->selectRaw('client_id, SUM(total - COALESCE(paid_amount, 0)) as owed')->pluck('owed', 'client_id');

        $rows = $q->get()->map(fn (Client $c) => [
            'id' => $c->id,
            'name' => $c->bill_to_name,
            'email' => $c->email,
            'active' => (bool) $c->is_active,
            'invoices' => (int) ($stats[$c->id]->n ?? 0),
            'invoiced' => round((float) ($stats[$c->id]->invoiced ?? 0), 2),
            'paid' => round((float) ($stats[$c->id]->paid ?? 0), 2),
            'owed' => round((float) ($owed[$c->id] ?? 0), 2),
            'created' => $c->created_at?->toDateString(),
            'url' => $this->link($c->bill_to_name, route('clients.show', $c->id, false)),
        ]);

        $rows = match ($a['sort'] ?? 'top') {
            'owed' => $rows->sortByDesc('owed'),
            'name' => $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'recent' => $rows->sortByDesc('created'),
            default => $rows->sortByDesc('invoiced'),
        };

        return ['currency' => $this->currency, 'client_count' => $rows->count(), 'clients' => $rows->take($this->limit($a))->values()->all()];
    }

    private function searchExpenses(array $a): array
    {
        $q = Expense::withoutGlobalScopes()->where('expenses.company_id', $this->cid);

        if (filled($a['search'] ?? null)) {
            $s = (string) $a['search'];
            $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('vendor', 'like', "%{$s}%")
                ->orWhere('reference', 'like', "%{$s}%")->orWhere('expense_number', 'like', "%{$s}%"));
        }
        if (filled($a['category'] ?? null)) {
            $q->where('category', $a['category']);
        }
        $this->dateRange($q, 'expense_date', $a);

        $byCategory = (clone $q)->groupBy('category')->selectRaw('category, SUM(amount) as t')->orderByDesc('t')->pluck('t', 'category')
            ->mapWithKeys(fn ($t, $c) => [Expense::categories()[$c] ?? $c => round((float) $t, 2)]);

        return [
            'currency' => $this->currency,
            'count' => (clone $q)->count(),
            'total_amount' => round((float) (clone $q)->sum('amount'), 2),
            'total_tax' => round((float) (clone $q)->sum('tax_amount'), 2),
            'by_category' => $byCategory->all(),
            'expenses' => $q->orderByDesc('expense_date')->orderByDesc('id')->limit($this->limit($a))->get()->map(fn (Expense $e) => [
                'number' => $e->expense_number,
                'title' => $e->title,
                'date' => $e->expense_date?->toDateString(),
                'category' => Expense::categories()[$e->category] ?? $e->category,
                'vendor' => $e->vendor,
                'amount' => (float) $e->amount,
                'tax' => (float) $e->tax_amount,
                'url' => $this->link($e->expense_number, route('expenses.show', $e->id, false)),
            ])->all(),
        ];
    }

    private function searchQuotations(array $a): array
    {
        $q = Quotation::withoutGlobalScopes()->where('quotations.company_id', $this->cid)
            ->with(['client' => fn ($c) => $c->withoutGlobalScopes()]);

        if (filled($a['status'] ?? null) && ! in_array($a['status'], ['any', 'all'], true)) {
            $q->where('status', $a['status']);
        }
        if ($client = $this->clientFilter($a)) {
            $q->where('client_id', $client->id);
        }
        if (filled($a['search'] ?? null)) {
            $s = (string) $a['search'];
            $q->where(fn ($w) => $w->where('quotation_number', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%"));
        }
        $this->dateRange($q, 'issue_date', $a);

        return [
            'currency' => $this->currency,
            'matching_count' => (clone $q)->count(),
            'matching_total' => round((float) (clone $q)->sum('total'), 2),
            'quotations' => $q->orderByDesc('issue_date')->orderByDesc('id')->limit($this->limit($a))->get()->map(fn (Quotation $x) => [
                'number' => $x->quotation_number,
                'client' => $x->client?->bill_to_name,
                'status' => $x->status,
                'issued' => $x->issue_date?->toDateString(),
                'valid_until' => $x->valid_until?->toDateString(),
                'total' => (float) $x->total,
                'url' => $this->link($x->quotation_number, route('quotations.show', $x->id, false)),
            ])->all(),
        ];
    }

    private function searchProducts(array $a): array
    {
        $q = Product::withoutGlobalScopes()->where('company_id', $this->cid);

        if (filled($a['search'] ?? null)) {
            $s = (string) $a['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }

        return [
            'currency' => $this->currency,
            'products' => $q->orderBy('name')->limit($this->limit($a))->get()->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'unit' => $p->unit,
                'tax_rate' => (float) $p->tax_rate,
                'active' => (bool) $p->is_active,
            ])->all(),
        ];
    }

    private function searchPayments(array $a): array
    {
        $q = Payment::withoutGlobalScopes()->where('payments.company_id', $this->cid)
            ->with(['client' => fn ($c) => $c->withoutGlobalScopes(), 'invoice' => fn ($i) => $i->withoutGlobalScopes()]);

        if ($client = $this->clientFilter($a)) {
            $q->where('client_id', $client->id);
        }
        $this->dateRange($q, 'payment_date', $a);

        $done = (clone $q)->where('status', Payment::STATUS_COMPLETED);

        return [
            'currency' => $this->currency,
            'completed_count' => (clone $done)->count(),
            'completed_total' => round((float) $done->sum('amount'), 2),
            'payments' => $q->orderByDesc('payment_date')->orderByDesc('id')->limit($this->limit($a))->get()->map(fn (Payment $p) => [
                'number' => $p->payment_number,
                'date' => $p->payment_date?->toDateString(),
                'client' => $p->client?->bill_to_name,
                'invoice' => $p->invoice?->invoice_number,
                'amount' => (float) $p->amount,
                'refunded' => (float) $p->refunded_amount,
                'method' => $p->payment_method,
                'status' => $p->status,
            ])->all(),
        ];
    }

    private function businessSummary(array $a): array
    {
        $from = $this->date($a['date_from'] ?? null) ?? today()->startOfMonth();
        $to = $this->date($a['date_to'] ?? null) ?? today();

        $invoiced = (float) Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->sum('total');
        $collected = (float) Payment::withoutGlobalScopes()->where('company_id', $this->cid)->where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum(DB::raw('amount - COALESCE(refunded_amount, 0)'));
        $expenses = Expense::withoutGlobalScopes()->where('company_id', $this->cid)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        $expenseTotal = (float) (clone $expenses)->sum('amount') + (float) (clone $expenses)->sum('tax_amount');

        $open = Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->whereIn('status', self::OPEN);
        $overdue = (clone $open)->whereDate('due_date', '<', today());

        return [
            'currency' => $this->currency,
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'invoiced' => round($invoiced, 2),
            'collected' => round($collected, 2),
            'expenses_incl_tax' => round($expenseTotal, 2),
            'profit_cash_basis' => round($collected - $expenseTotal, 2),
            'outstanding_now' => ['count' => (clone $open)->count(), 'balance' => round((float) $open->sum(DB::raw('total - COALESCE(paid_amount, 0)')), 2)],
            'overdue_now' => ['count' => (clone $overdue)->count(), 'balance' => round((float) $overdue->sum(DB::raw('total - COALESCE(paid_amount, 0)')), 2)],
            'draft_invoices' => Invoice::withoutGlobalScopes()->where('company_id', $this->cid)->where('status', 'draft')->count(),
        ];
    }

    /* ------------------------------- Writes ------------------------------ */

    /** Check a draft invoice or quotation and park it for confirmation. */
    private function draftDocument(string $type, array $a): array
    {
        $client = $this->resolveClient($a['client'] ?? null);
        $settings = $this->company->settings;
        $defaultTax = $settings?->tax_enabled ? (float) $settings->default_tax_rate : 0.0;

        $items = collect(is_array($a['items'] ?? null) ? $a['items'] : [])->take(50)->values();
        if ($items->isEmpty()) {
            throw new ToolError('Add at least one line item.');
        }

        $items = $items->map(function ($item, $i) use ($defaultTax) {
            $item = is_array($item) ? $item : [];
            $product = filled($item['product_id'] ?? null)
                ? Product::withoutGlobalScopes()->where('company_id', $this->cid)->find((int) $item['product_id'])
                : null;
            if (filled($item['product_id'] ?? null) && ! $product) {
                throw new ToolError('Line ' . ($i + 1) . ': unknown product_id.');
            }

            $name = trim((string) ($item['name'] ?? $product?->name ?? ''));
            $qty = (float) ($item['quantity'] ?? 1);
            $price = $item['price'] ?? $product?->price;
            if ($name === '' || ! is_numeric($price) || $qty <= 0 || $price < 0) {
                throw new ToolError('Line ' . ($i + 1) . ' needs a name, a quantity above 0 and a price. Ask the user for the price if you do not know it.');
            }

            return [
                'product_id' => $product?->id,
                'name' => mb_substr($name, 0, 255),
                'description' => filled($item['description'] ?? null) ? mb_substr((string) $item['description'], 0, 2000) : ($product?->description ?: null),
                'quantity' => round($qty, 2),
                'unit' => array_key_exists((string) ($item['unit'] ?? ''), Product::units()) ? $item['unit'] : ($product?->unit ?: 'unit'),
                'price' => round((float) $price, 2),
                'tax_rate' => round((float) ($item['tax_rate'] ?? $product?->tax_rate ?? $defaultTax), 2),
            ];
        })->all();

        $issue = $this->date($a['issue_date'] ?? null) ?? today();
        $end = $type === 'invoice'
            ? ($this->date($a['due_date'] ?? null) ?? $issue->copy()->addDays((int) ($this->company->payment_terms ?: 30)))
            : ($this->date($a['valid_until'] ?? null) ?? $issue->copy()->addDays((int) ($this->company->quotation_validity_days ?: 30)));
        if ($end->lt($issue)) {
            throw new ToolError(($type === 'invoice' ? 'The due date' : 'The valid-until date') . ' cannot be before the issue date.');
        }

        $discountType = ($a['discount_type'] ?? 'fixed') === 'percentage' ? 'percentage' : 'fixed';
        $discount = max(0, (float) ($a['discount'] ?? 0));
        if ($discountType === 'percentage' && $discount > 100) {
            throw new ToolError('A percentage discount cannot exceed 100.');
        }

        if ($type === 'invoice' && $this->company->hasReachedLimit('invoices')) {
            throw new ToolError('The plan\'s invoice limit has been reached (or the subscription is not active), so no invoice can be created. Tell the user to upgrade the plan.');
        }

        // Preview totals with the same math the saved document uses.
        $preview = new Invoice(['discount_amount' => $discount, 'discount_type' => $discountType]);
        $preview->setRelation('items', collect($items)->map(fn ($i) => new InvoiceItem($i)));
        $preview->calculateTotals();

        $payload = [
            'client_id' => $client->id,
            'reference' => filled($a['reference'] ?? null) ? mb_substr((string) $a['reference'], 0, 255) : null,
            'issue_date' => $issue->toDateString(),
            $type === 'invoice' ? 'due_date' : 'valid_until' => $end->toDateString(),
            'notes' => filled($a['notes'] ?? null) ? mb_substr((string) $a['notes'], 0, 5000) : ($settings->default_notes ?? null),
            'terms' => $settings->default_terms ?? null,
            'discount_amount' => $discount,
            'discount_type' => $discountType,
            'items' => $items,
        ];

        $lines = [
            "Client: {$client->bill_to_name}",
            'Issue date: ' . $issue->toDateString() . ' · ' . ($type === 'invoice' ? 'Due: ' : 'Valid until: ') . $end->toDateString(),
            ...collect($items)->map(fn ($i) => "{$i['name']}: {$i['quantity']} × " . $this->money($i['price']) . ($i['tax_rate'] > 0 ? " + {$i['tax_rate']}% tax" : '')),
        ];
        if ($discount > 0) {
            $lines[] = 'Discount: ' . ($discountType === 'percentage' ? "{$discount}%" : $this->money($discount));
        }

        $label = $type === 'invoice' ? 'invoice' : 'quotation';

        return $this->park($type, $payload, "Create draft {$label}", $lines, $this->money((float) $preview->total), [
            'client' => $client->bill_to_name,
            'subtotal' => round((float) $preview->subtotal, 2),
            'tax' => round((float) $preview->tax_amount, 2),
            'total' => round((float) $preview->total, 2),
        ]);
    }

    private function draftExpense(array $a): array
    {
        $title = trim((string) ($a['title'] ?? ''));
        $amount = $a['amount'] ?? null;
        if ($title === '' || ! is_numeric($amount) || $amount < 0.01) {
            throw new ToolError('An expense needs a title and an amount above 0.');
        }
        $category = (string) ($a['category'] ?? 'other');
        if (! array_key_exists($category, Expense::categories())) {
            throw new ToolError('Unknown category. Use one of: ' . implode(', ', array_keys(Expense::categories())) . '.');
        }
        $method = (string) ($a['payment_method'] ?? 'other');
        if (! array_key_exists($method, Expense::paymentMethods())) {
            $method = 'other';
        }
        $date = $this->date($a['expense_date'] ?? null) ?? today();
        $tax = max(0, (float) ($a['tax_amount'] ?? 0));

        $payload = [
            'title' => mb_substr($title, 0, 255),
            'category' => $category,
            'amount' => round((float) $amount, 2),
            'tax_amount' => round($tax, 2),
            'vendor' => filled($a['vendor'] ?? null) ? mb_substr((string) $a['vendor'], 0, 255) : null,
            'payment_method' => $method,
            'reference' => filled($a['reference'] ?? null) ? mb_substr((string) $a['reference'], 0, 100) : null,
            'description' => filled($a['description'] ?? null) ? mb_substr((string) $a['description'], 0, 2000) : null,
            'expense_date' => $date->toDateString(),
        ];

        $lines = array_values(array_filter([
            $payload['title'],
            'Category: ' . Expense::categories()[$category],
            'Date: ' . $payload['expense_date'],
            $payload['vendor'] ? "Vendor: {$payload['vendor']}" : null,
            'Paid by: ' . Expense::paymentMethods()[$method],
            $tax > 0 ? 'Tax: ' . $this->money($tax) : null,
        ]));

        return $this->park('expense', $payload, 'Record expense', $lines, $this->money($payload['amount'] + $payload['tax_amount']), [
            'amount' => $payload['amount'], 'tax' => $payload['tax_amount'],
        ]);
    }

    private function park(string $type, array $payload, string $title, array $lines, string $total, array $facts): array
    {
        $token = Str::random(40);

        Cache::put(self::cacheKey($token), [
            'type' => $type,
            'user_id' => $this->user->id,
            'company_id' => $this->cid,
            'payload' => $payload,
        ], now()->addMinutes(self::ACTION_TTL_MINUTES));

        $this->actions[] = ['token' => $token, 'type' => $type, 'title' => $title, 'lines' => $lines, 'total' => $total];

        return [
            'status' => 'awaiting_user_confirmation',
            'note' => 'Nothing has been saved yet. A summary card with a Confirm button is shown to the user; it is created only when they click Confirm.',
            'currency' => $this->currency,
            ...$facts,
        ];
    }

    /**
     * Create a parked action for the user who proposed it, through the normal create
     * endpoint. Single use: the token is claimed before anything runs.
     *
     * @return array{ok: bool, message: string, url?: string, label?: string}
     */
    public static function confirm(string $token, User $user): array
    {
        $key = self::cacheKey($token);
        $action = Cache::get($key);

        // Owner check first so someone else's attempt can't burn the token; then claim it.
        // forget() reports whether this request deleted it, so a double click creates once.
        if (! $action || $action['user_id'] !== $user->id || $action['company_id'] !== $user->company_id || ! Cache::forget($key)) {
            return ['ok' => false, 'message' => 'This action has expired or was already used. Ask the assistant again.'];
        }

        $company = $user->company;
        $payload = $action['payload'];

        try {
            return match ($action['type']) {
                'invoice' => self::createInvoice($company, $payload),
                'quotation' => self::createQuotation($company, $payload),
                'expense' => self::createExpense($user, $payload),
            };
        } catch (ValidationException $e) {
            return ['ok' => false, 'message' => collect($e->errors())->flatten()->first() ?? 'The record could not be saved.'];
        }
    }

    private static function createInvoice(Company $company, array $payload): array
    {
        if ($company->hasReachedLimit('invoices')) {
            return ['ok' => false, 'message' => 'You have reached the invoice limit for your current plan. Please upgrade to create more invoices.'];
        }

        $number = $company->getNextInvoiceNumber();
        app(InvoiceController::class)->store(self::formRequest([...$payload, 'invoice_number' => $number]));

        $invoice = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->where('invoice_number', $number)->first();

        return $invoice
            ? ['ok' => true, 'message' => "Draft invoice {$number} created.", 'label' => $number, 'url' => route('invoices.show', $invoice->id, false)]
            : ['ok' => false, 'message' => 'The invoice could not be created.'];
    }

    private static function createQuotation(Company $company, array $payload): array
    {
        $number = $company->getNextQuotationNumber();
        app(QuotationController::class)->store(self::formRequest([...$payload, 'quotation_number' => $number]));

        $quotation = Quotation::withoutGlobalScopes()->where('company_id', $company->id)->where('quotation_number', $number)->first();

        return $quotation
            ? ['ok' => true, 'message' => "Draft quotation {$number} created.", 'label' => $number, 'url' => route('quotations.show', $quotation->id, false)]
            : ['ok' => false, 'message' => 'The quotation could not be created.'];
    }

    private static function createExpense(User $user, array $payload): array
    {
        $before = (int) Expense::withoutGlobalScopes()->where('company_id', $user->company_id)->max('id');
        app(ExpenseController::class)->store(self::formRequest($payload));

        $expense = Expense::withoutGlobalScopes()->where('company_id', $user->company_id)->where('user_id', $user->id)
            ->where('id', '>', $before)->latest('id')->first();

        return $expense
            ? ['ok' => true, 'message' => "Expense {$expense->expense_number} recorded.", 'label' => $expense->expense_number, 'url' => route('expenses.show', $expense->id, false)]
            : ['ok' => false, 'message' => 'The expense could not be recorded.'];
    }

    /** A POST like the create form's, for the signed-in user. */
    private static function formRequest(array $data): Request
    {
        $current = request();
        $sub = Request::create($current->url(), 'POST', $data);
        $sub->setUserResolver(fn () => auth()->user());
        if ($current->hasSession()) {
            $sub->setLaravelSession($current->session());
        }

        return $sub;
    }

    private static function cacheKey(string $token): string
    {
        return 'ai-action:' . hash('sha256', $token);
    }

    /* ------------------------------ Helpers ------------------------------ */

    private function invoiceRow(Invoice $i): array
    {
        $balance = max(0, round((float) $i->total - (float) $i->paid_amount, 2));

        return [
            'number' => $i->invoice_number,
            'client' => $i->client?->bill_to_name,
            'status' => $i->status,
            'issued' => $i->issue_date?->toDateString(),
            'due' => $i->due_date?->toDateString(),
            'total' => round((float) $i->total, 2),
            'paid' => round((float) $i->paid_amount, 2),
            'balance' => $balance,
            'days_overdue' => in_array($i->status, self::OPEN, true) && $i->due_date?->lt(today()) ? (int) $i->due_date->diffInDays(today()) : 0,
            'url' => $this->link($i->invoice_number, route('invoices.show', $i->id, false)),
        ];
    }

    private function clientFilter(array $a): ?Client
    {
        return filled($a['client'] ?? null) ? $this->resolveClient($a['client']) : null;
    }

    /** A client of this company by id or name; ambiguous names are reported back. */
    private function resolveClient(mixed $ref): Client
    {
        if (blank($ref)) {
            throw new ToolError('Which client? Give a client name or id (use search_clients).');
        }

        $base = Client::withoutGlobalScopes()->where('company_id', $this->cid);

        if (is_numeric($ref) && $client = (clone $base)->find((int) $ref)) {
            return $client;
        }

        $name = trim((string) $ref);
        $exact = (clone $base)->where(fn ($w) => $w->where('name', $name)->orWhere('company_name', $name)->orWhere('email', $name))->get();
        $matches = $exact->isNotEmpty() ? $exact
            : (clone $base)->where(fn ($w) => $w->where('name', 'like', "%{$name}%")->orWhere('company_name', 'like', "%{$name}%"))->limit(6)->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->isEmpty()) {
            throw new ToolError("No client matches \"{$name}\". Ask the user to pick an existing client or add the client first (Clients → New client).");
        }

        throw new ToolError("Several clients match \"{$name}\": " . $matches->map(fn ($c) => "{$c->bill_to_name} (id {$c->id})")->implode(', ') . '. Ask which one.');
    }

    private function dateRange($query, string $column, array $a): void
    {
        if ($from = $this->date($a['date_from'] ?? null)) {
            $query->whereDate($column, '>=', $from->toDateString());
        }
        if ($to = $this->date($a['date_to'] ?? null)) {
            $query->whereDate($column, '<=', $to->toDateString());
        }
    }

    private function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', (string) $value) ?: throw new \InvalidArgumentException();
        } catch (\Throwable) {
            throw new ToolError("Invalid date \"{$value}\"; use YYYY-MM-DD.");
        }
    }

    private function limit(array $a): int
    {
        return max(1, min(self::LIMIT, (int) ($a['limit'] ?? 10)));
    }

    private function link(string $label, string $path): string
    {
        $this->links[$label] = $path;

        return $path;
    }

    private function money(float $amount): string
    {
        return $this->currency . ' ' . number_format($amount, 2);
    }

    /** @return Collection<int, array{label: string, url: string}> links to records the answer mentions */
    public function linksIn(string $text): Collection
    {
        return collect($this->links)->filter(fn ($url, $label) => $label !== '' && str_contains($text, (string) $label))
            ->map(fn ($url, $label) => ['label' => (string) $label, 'url' => $url])->values()->take(8);
    }
}
