<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public const RANGES = [
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_quarter' => 'This quarter',
        'last_quarter' => 'Last quarter',
        'this_year' => 'This year',
        'last_year' => 'Last year',
        'last_30_days' => 'Last 30 days',
        'last_12_months' => 'Last 12 months',
        'custom' => 'Custom range',
    ];

    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $range = array_key_exists($request->get('date_range'), self::RANGES) ? $request->get('date_range') : 'this_month';
        [$start, $end] = $this->dateRange($range, $request);
        [$prevStart, $prevEnd] = [(clone $start)->sub($start->diff($end))->subSecond(), (clone $start)->subSecond()];

        // Revenue = money actually collected (completed payments, net of refunds), so partial
        // payments and refunds are reflected. Previously: totals of fully-paid invoices only.
        $collected = fn ($from, $to) => (float) Payment::whereIn('status', ['completed', 'partially_refunded', 'refunded'])
            ->whereBetween('payment_date', [$from, $to])
            ->sum(DB::raw('amount - COALESCE(refunded_amount, 0)'));
        $spent = fn ($from, $to) => (float) Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum(DB::raw('amount + COALESCE(tax_amount, 0)'));

        $revenue = $collected($start, $end);
        $expenses = $spent($start, $end);
        $prevRevenue = $collected($prevStart, $prevEnd);
        $prevExpenses = $spent($prevStart, $prevEnd);
        $profit = $revenue - $expenses;

        $invoiced = (float) Invoice::whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')->sum('total');

        return Inertia::render('Reports/Index', [
            'range' => $range,
            'ranges' => self::RANGES,
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'kpis' => [
                'revenue' => $revenue,
                'revenue_trend' => $this->trend($revenue, $prevRevenue),
                'expenses' => $expenses,
                'expenses_trend' => $this->trend($expenses, $prevExpenses),
                'profit' => $profit,
                'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
                'invoiced' => $invoiced,
                'invoices_issued' => Invoice::whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->count(),
                'new_clients' => Client::whereBetween('created_at', [$start, $end])->count(),
                'outstanding' => (float) Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
                'overdue' => (float) Invoice::where('status', 'overdue')->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
            ],
            'monthly' => $this->monthly(),
            'aging' => $this->aging(),
            'topClients' => Payment::whereIn('status', ['completed', 'partially_refunded'])
                ->whereBetween('payment_date', [$start, $end])
                ->select('client_id', DB::raw('SUM(amount - COALESCE(refunded_amount, 0)) as revenue'), DB::raw('COUNT(*) as payments'))
                ->groupBy('client_id')->orderByDesc('revenue')->limit(8)
                ->with('client:id,name,company_name,client_type')->get()
                ->map(fn ($r) => ['id' => $r->client_id, 'name' => $r->client?->bill_to_name ?? 'Unknown', 'revenue' => (float) $r->revenue, 'payments' => (int) $r->payments]),
            'topProducts' => DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.company_id', $company->id)
                ->whereNotIn('invoices.status', ['draft', 'cancelled'])
                ->whereBetween('invoices.issue_date', [$start->toDateString(), $end->toDateString()])
                ->select('invoice_items.name', DB::raw('SUM(invoice_items.quantity) as quantity'), DB::raw('SUM(invoice_items.quantity * invoice_items.price) as revenue'))
                ->groupBy('invoice_items.name')->orderByDesc('revenue')->limit(8)->get()
                ->map(fn ($r) => ['name' => $r->name, 'quantity' => (float) $r->quantity, 'revenue' => (float) $r->revenue]),
            'paymentMethods' => Payment::where('status', 'completed')->whereBetween('payment_date', [$start, $end])
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')->orderByDesc('total')->get()
                ->map(fn ($r) => ['method' => Payment::paymentMethods()[$r->payment_method] ?? ucwords(str_replace('_', ' ', $r->payment_method)), 'count' => (int) $r->count, 'total' => (float) $r->total]),
            'expensesByCategory' => Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                ->select('category', DB::raw('SUM(amount + COALESCE(tax_amount, 0)) as total'))
                ->groupBy('category')->orderByDesc('total')->get()
                ->map(fn ($r) => ['category' => Expense::categories()[$r->category] ?? ucfirst($r->category), 'total' => (float) $r->total]),
        ]);
    }

    /** Collected revenue, expenses and profit for each of the last 12 months. */
    private function monthly(): array
    {
        $from = now()->subMonthsNoOverflow(11)->startOfMonth();

        $rev = Payment::whereIn('status', ['completed', 'partially_refunded', 'refunded'])->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount - COALESCE(refunded_amount, 0)) as total")
            ->groupBy('ym')->pluck('total', 'ym');
        $exp = Expense::where('expense_date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount + COALESCE(tax_amount, 0)) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        return collect(range(0, 11))->map(function ($i) use ($from, $rev, $exp) {
            $m = (clone $from)->addMonthsNoOverflow($i);
            $key = $m->format('Y-m');
            $r = round((float) ($rev[$key] ?? 0), 2);
            $e = round((float) ($exp[$key] ?? 0), 2);

            return ['month' => $m->format('M'), 'label' => $m->format('M Y'), 'revenue' => $r, 'expenses' => $e, 'profit' => round($r - $e, 2)];
        })->all();
    }

    /** Outstanding balances bucketed by days past due. */
    private function aging(): array
    {
        $buckets = ['current' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '90_plus' => 0.0];
        $today = now()->startOfDay();

        Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->get(['id', 'due_date', 'total', 'paid_amount'])
            ->each(function ($inv) use (&$buckets, $today) {
                $balance = max(0, (float) $inv->total - (float) $inv->paid_amount);
                $days = $inv->due_date ? (int) $inv->due_date->diffInDays($today, false) : 0;
                $key = $days <= 0 ? 'current' : ($days <= 30 ? '1_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : '90_plus')));
                $buckets[$key] += $balance;
            });

        return array_map(fn ($v) => round($v, 2), $buckets);
    }

    private function trend(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function dateRange(string $range, Request $request): array
    {
        $now = now();

        // NoOverflow variants: on the 29th–31st, subMonth() lands in the *current* month
        // (e.g. Mar 31 - 1 month = Mar 3), so "last month" showed this month.
        return match ($range) {
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarter' => [$now->copy()->startOfQuarter()->subMonthsNoOverflow(3), $now->copy()->startOfQuarter()->subDay()->endOfDay()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYearNoOverflow()->startOfYear(), $now->copy()->subYearNoOverflow()->endOfYear()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last_12_months' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->copy()->endOfMonth()],
            'custom' => $this->customRange($request),
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function customRange(Request $request): array
    {
        $from = rescue(fn () => Carbon::parse($request->get('from'))->startOfDay(), now()->startOfMonth(), false);
        $to = rescue(fn () => Carbon::parse($request->get('to'))->endOfDay(), now()->endOfDay(), false);

        return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }
}
