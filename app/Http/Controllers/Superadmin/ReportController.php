<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    private const RANGES = [
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_quarter' => 'This quarter',
        'this_year' => 'This year',
        'last_year' => 'Last year',
        'last_12_months' => 'Last 12 months',
    ];

    public function index(Request $request)
    {
        $range = array_key_exists($request->get('date_range'), self::RANGES) ? $request->get('date_range') : 'this_month';
        [$start, $end] = $this->range($range);
        $platform = fn () => Payment::withoutGlobalScopes()->where('via_app_gateway', true)->where('status', 'completed');

        $mrrCase = "CASE WHEN subscription_plans.billing_cycle = 'yearly' THEN subscription_plans.price / 12 ELSE subscription_plans.price END";
        $mrr = (float) Company::where('subscription_status', 'active')
            ->join('subscription_plans', 'companies.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum(DB::raw($mrrCase));

        $from = now()->subMonthsNoOverflow(11)->startOfMonth();
        $volume = $platform()->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as t")->groupBy('ym')->pluck('t', 'ym');
        $signups = Company::where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")->groupBy('ym')->pluck('c', 'ym');

        return Inertia::render('Superadmin/Reports/Index', [
            'range' => $range,
            'ranges' => self::RANGES,
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'kpis' => [
                'companies' => Company::count(),
                'new_companies' => Company::whereBetween('created_at', [$start, $end])->count(),
                'active_companies' => Company::where('subscription_status', 'active')->count(),
                'mrr' => round($mrr, 2),
                'arr' => round($mrr * 12, 2),
                'volume' => (float) $platform()->whereBetween('payment_date', [$start, $end])->sum('amount'),
                'payments' => $platform()->whereBetween('payment_date', [$start, $end])->count(),
                'volume_total' => (float) $platform()->sum('amount'),
                'paid_out' => (float) Payout::withoutGlobalScopes()->where('status', 'completed')->sum('amount'),
                'pending_payouts' => (float) Payout::withoutGlobalScopes()->whereIn('status', ['pending', 'processing'])->sum('amount'),
                'fees' => (float) Payout::withoutGlobalScopes()->where('status', 'completed')->sum('fee_amount'),
            ],
            'monthly' => collect(range(0, 11))->map(function ($i) use ($from, $volume, $signups) {
                $m = (clone $from)->addMonthsNoOverflow($i);
                $k = $m->format('Y-m');

                return ['month' => $m->format('M'), 'label' => $m->format('M Y'), 'volume' => round((float) ($volume[$k] ?? 0), 2), 'signups' => (int) ($signups[$k] ?? 0)];
            }),
            'plans' => SubscriptionPlan::withCount([
                'companies',
                'companies as active_count' => fn ($q) => $q->where('subscription_status', 'active'),
            ])->ordered()->get()->map(fn ($p) => [
                'name' => $p->name,
                'price' => (float) $p->price,
                'billing_cycle' => $p->billing_cycle,
                'companies' => $p->companies_count,
                'active' => $p->active_count,
                'mrr' => round($p->active_count * ($p->billing_cycle === 'yearly' ? $p->price / 12 : $p->price), 2),
            ]),
            'topCompanies' => $platform()->whereBetween('payment_date', [$start, $end])
                ->select('company_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('company_id')->orderByDesc('total')->limit(10)
                ->with('company:id,name')->get()
                ->map(fn ($r) => ['name' => $r->company?->name ?? 'Deleted company', 'total' => (float) $r->total, 'count' => (int) $r->count]),
        ]);
    }

    private function range(string $range): array
    {
        $now = now();

        // NoOverflow: subMonth() on the 29th–31st lands in the current month.
        return match ($range) {
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYearNoOverflow()->startOfYear(), $now->copy()->subYearNoOverflow()->endOfYear()],
            'last_12_months' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
