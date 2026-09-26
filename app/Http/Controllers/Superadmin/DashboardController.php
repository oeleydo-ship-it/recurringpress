<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $from = now()->subMonthsNoOverflow(11)->startOfMonth();
        $signups = Company::where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")->groupBy('ym')->pluck('c', 'ym');
        $volume = Payment::withoutGlobalScopes()->where('via_app_gateway', true)->where('status', 'completed')->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as t")->groupBy('ym')->pluck('t', 'ym');

        $series = collect(range(0, 11))->map(function ($i) use ($from, $signups, $volume) {
            $m = (clone $from)->addMonthsNoOverflow($i);
            $k = $m->format('Y-m');

            return ['month' => $m->format('M'), 'label' => $m->format('M Y'), 'signups' => (int) ($signups[$k] ?? 0), 'volume' => round((float) ($volume[$k] ?? 0), 2)];
        });

        return Inertia::render('Superadmin/Dashboard', [
            'stats' => [
                'companies' => Company::count(),
                'companies_30d' => Company::where('created_at', '>=', now()->subDays(30))->count(),
                'users' => User::count(),
                'mrr' => $this->mrr(),
                'paying' => Company::where('subscription_status', 'active')->whereHas('subscriptionPlan', fn ($q) => $q->where('price', '>', 0))->count(),
                'trials' => Company::where('subscription_status', 'trial')->count(),
                'invoices' => Invoice::withoutGlobalScopes()->count(),
                'volume_30d' => (float) Payment::withoutGlobalScopes()->where('via_app_gateway', true)->where('status', 'completed')->where('payment_date', '>=', now()->subDays(30))->sum('amount'),
                'pending_payouts' => (float) Payout::withoutGlobalScopes()->whereIn('status', ['pending', 'processing'])->sum('amount'),
                'pending_payouts_count' => Payout::withoutGlobalScopes()->whereIn('status', ['pending', 'processing'])->count(),
                'pages' => CmsPage::count(),
                'posts' => CmsPost::count(),
            ],
            'series' => $series,
            'recentCompanies' => Company::with(['subscriptionPlan:id,name', 'users' => fn ($q) => $q->where('role', 'owner')->select('id', 'company_id')])
                ->withCount('users')->latest()->take(6)->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'plan' => $c->subscriptionPlan?->name,
                    'status' => $c->subscription_status,
                    'users' => $c->users_count,
                    'owner_id' => $c->users->first()?->id,
                    'created_at' => $c->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /** Monthly recurring revenue: monthly plans + yearly plans / 12, active tenants. */
    private function mrr(): float
    {
        return round((float) Company::where('subscription_status', 'active')
            ->join('subscription_plans', 'companies.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum(DB::raw("CASE WHEN subscription_plans.billing_cycle = 'yearly' THEN subscription_plans.price / 12 ELSE subscription_plans.price END")), 2);
    }
}
