<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /** Invoice statuses that still have money owed on them. */
    private const OPEN_STATUSES = ['sent', 'viewed', 'partial', 'overdue'];

    public function index()
    {
        $user = auth()->user();
        $company = $user->company->load('subscriptionPlan');

        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        // Money actually received comes from completed payments (covers partial payments too).
        $collectedThisMonth = (float) Payment::where('status', 'completed')->where('payment_date', '>=', $monthStart)->sum('amount');
        $collectedLastMonth = (float) Payment::where('status', 'completed')->whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])->sum('amount');

        $expensesThisMonth = (float) Expense::where('expense_date', '>=', $monthStart)->sum('amount');
        $expensesLastMonth = (float) Expense::whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])->sum('amount');

        $open = Invoice::whereIn('status', self::OPEN_STATUSES);
        $outstanding = (float) (clone $open)->sum(DB::raw('total - COALESCE(paid_amount, 0)'));
        $overdueQuery = Invoice::where(function ($q) {
            $q->where('status', 'overdue')
                ->orWhere(fn ($q) => $q->whereIn('status', ['sent', 'viewed', 'partial'])->where('due_date', '<', now()->startOfDay()));
        });

        $stats = [
            'collected_month' => $collectedThisMonth,
            'collected_trend' => $this->trend($collectedThisMonth, $collectedLastMonth),
            'outstanding' => $outstanding,
            'open_invoices' => (clone $open)->count(),
            'overdue_amount' => (float) (clone $overdueQuery)->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
            'overdue_count' => (clone $overdueQuery)->count(),
            'expenses_month' => $expensesThisMonth,
            'expenses_trend' => $this->trend($expensesThisMonth, $expensesLastMonth),
            'clients' => Client::count(),
            'draft_invoices' => Invoice::where('status', 'draft')->count(),
            'open_quotations' => Quotation::whereIn('status', ['draft', 'sent'])->count(),
            'collected_total' => (float) Payment::where('status', 'completed')->sum('amount'),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'chart' => $this->monthlySeries(),
            'statusBreakdown' => Invoice::select('status', DB::raw('count(*) as count'), DB::raw('SUM(total) as amount'))
                ->groupBy('status')->get()
                ->map(fn ($r) => ['status' => $r->status, 'count' => (int) $r->count, 'amount' => (float) $r->amount])
                ->values(),
            'recentInvoices' => Invoice::with('client:id,name,company_name,client_type,email')
                ->latest()->take(6)->get()
                ->map(fn (Invoice $i) => $this->invoiceRow($i)),
            'overdueInvoices' => (clone $overdueQuery)->with('client:id,name,company_name,client_type')
                ->orderBy('due_date')->take(5)->get()
                ->map(fn (Invoice $i) => $this->invoiceRow($i)),
            'usage' => $this->usage($company),
            // Last AI analysis (cached up to a day); a new one is generated on request.
            'aiInsights' => \App\Services\Ai\AiClient::enabled()
                ? AiController::withInvoiceLinks(\App\Services\Ai\AiFeatures::cachedInsights($company), $company)
                : null,
            'greeting' => $this->greeting(),
        ]);
    }

    private function invoiceRow(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client' => $invoice->client?->bill_to_name,
            'total' => (float) $invoice->total,
            'balance' => max(0, (float) $invoice->total - (float) $invoice->paid_amount),
            'status' => $invoice->status,
            'issue_date' => $invoice->issue_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
        ];
    }

    /** Last 12 months of collected revenue vs expenses. */
    private function monthlySeries(): array
    {
        $from = now()->subMonthsNoOverflow(11)->startOfMonth();

        $revenue = Payment::where('status', 'completed')->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $expenses = Expense::where('expense_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $series = [];
        for ($i = 0; $i < 12; $i++) {
            $month = (clone $from)->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $series[] = [
                'month' => $month->format('M'),
                'label' => $month->format('M Y'),
                'revenue' => round((float) ($revenue[$key] ?? 0), 2),
                'expenses' => round((float) ($expenses[$key] ?? 0), 2),
            ];
        }

        return $series;
    }

    /** Current plan, its status and usage against each plan limit, for the dashboard header. */
    private function usage($company): array
    {
        $plan = $company->subscriptionPlan ?? SubscriptionPlan::where('slug', 'free')->first();
        $make = function (string $label, int $used, $limit) {
            $limit = $limit !== null ? (int) $limit : null;
            return ['label' => $label, 'used' => $used, 'limit' => $limit, 'percent' => $limit ? min(100, round($used / $limit * 100)) : 0];
        };

        $limits = [
            'invoices' => $make('Invoices', $company->invoices()->count(), $plan?->max_invoices),
            'clients' => $make('Clients', $company->clients()->count(), $plan?->max_clients),
            'users' => $make('Team members', $company->users()->count(), $plan?->max_users),
            'products' => $make('Products', $company->products()->count(), $plan?->max_products),
        ];

        $status = $company->subscription_status ?? 'active';
        // A trial or paid term whose end date has passed is effectively lapsed.
        if (in_array($status, ['active', 'trial'], true) && $company->subscription_ends_at?->isPast()) {
            $status = 'expired';
        }

        $isFree = ! $plan || $plan->isFree();
        $nearLimit = collect($limits)->contains(fn ($l) => $l['limit'] !== null && $l['percent'] >= 80);
        $lapsed = in_array($status, ['past_due', 'cancelled', 'expired'], true);

        return [
            'plan' => $plan?->name ?? 'Free',
            'price' => $plan?->formatted_price ?? 'Free',
            'is_free' => $isFree,
            'status' => $status,
            'ends_at' => $company->subscription_ends_at?->toDateString(),
            'limits' => $limits,
            'needs_upgrade' => $isFree || $nearLimit || $lapsed,
            'reason' => $lapsed ? 'lapsed' : ($nearLimit ? 'limit' : ($isFree ? 'free' : null)),
        ];
    }

    private function trend(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function greeting(): string
    {
        $hour = (int) Carbon::now()->format('G');

        return $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    }
}
