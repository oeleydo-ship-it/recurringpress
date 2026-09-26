<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', 'this_month');
        $dates = $this->getDateRange($dateRange);

        // Users/Companies Report
        $totalCompanies = Company::count();
        $newCompanies = Company::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
        $activeCompanies = Company::where('subscription_status', 'active')->count();

        // Subscription Report
        $subscriptionsByPlan = SubscriptionPlan::withCount(['companies' => function ($query) {
            $query->where('subscription_status', 'active');
        }])->orderBy('price')->get();

        $totalMRR = Company::where('subscription_status', 'active')
            ->join('subscription_plans', 'companies.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('subscription_plans.billing_cycle', 'monthly')
            ->sum('subscription_plans.price');

        $totalARR = Company::where('subscription_status', 'active')
            ->join('subscription_plans', 'companies.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('subscription_plans.billing_cycle', 'yearly')
            ->sum('subscription_plans.price');

        // Payments Report (via app gateway)
        $totalPayments = Payment::withoutGlobalScopes()
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->sum('amount');

        $periodPayments = Payment::withoutGlobalScopes()
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$dates['start'], $dates['end']])
            ->sum('amount');

        $paymentCount = Payment::withoutGlobalScopes()
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$dates['start'], $dates['end']])
            ->count();

        // Payout Report
        $totalPayouts = Payout::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('amount');

        $pendingPayouts = Payout::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // Revenue by month (last 12 months)
        $revenueByMonth = Payment::withoutGlobalScopes()
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->where('payment_date', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Top companies by payment volume
        $topCompanies = Payment::withoutGlobalScopes()
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->select('company_id', DB::raw('SUM(amount) as total_payments'), DB::raw('COUNT(*) as payment_count'))
            ->groupBy('company_id')
            ->orderByDesc('total_payments')
            ->limit(10)
            ->with('company:id,name,email')
            ->get();

        return view('superadmin.reports.index', compact(
            'dateRange',
            'totalCompanies',
            'newCompanies',
            'activeCompanies',
            'subscriptionsByPlan',
            'totalMRR',
            'totalARR',
            'totalPayments',
            'periodPayments',
            'paymentCount',
            'totalPayouts',
            'pendingPayouts',
            'revenueByMonth',
            'topCompanies'
        ));
    }

    protected function getDateRange($range): array
    {
        switch ($range) {
            case 'today':
                return ['start' => now()->startOfDay(), 'end' => now()->endOfDay()];
            case 'yesterday':
                return ['start' => now()->subDay()->startOfDay(), 'end' => now()->subDay()->endOfDay()];
            case 'this_week':
                return ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()];
            case 'last_week':
                return ['start' => now()->subWeek()->startOfWeek(), 'end' => now()->subWeek()->endOfWeek()];
            case 'this_month':
                return ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()];
            case 'last_month':
                return ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()];
            case 'this_year':
                return ['start' => now()->startOfYear(), 'end' => now()->endOfYear()];
            case 'last_year':
                return ['start' => now()->subYear()->startOfYear(), 'end' => now()->subYear()->endOfYear()];
            default:
                return ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()];
        }
    }
}
