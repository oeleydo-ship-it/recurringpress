<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $stats = [
            'total_invoices' => Invoice::count(),
            'total_clients' => Client::count(),
            'total_products' => Product::count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
            'pending_amount' => Invoice::whereIn('status', ['sent', 'overdue'])->sum('total'),
            'draft_invoices' => Invoice::where('status', 'draft')->count(),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
        ];

        $recentInvoices = Invoice::with('client')
            ->latest()
            ->take(5)
            ->get();

        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->select(
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $monthlyRevenue[$i] ?? 0;
        }

        $statusDistribution = Invoice::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $company = $user->company;
        $company->load('subscriptionPlan');
        $plan = $company->subscriptionPlan ?? \App\Models\SubscriptionPlan::where('slug', 'free')->first();

        $subscriptionUsage = [
            'invoices' => [
                'used' => $company->invoices()->count(),
                'limit' => $plan->max_invoices ?? 'Unlimited',
                'percent' => $plan && $plan->max_invoices ? min(($company->invoices()->count() / $plan->max_invoices) * 100, 100) : 0
            ],
            'clients' => [
                'used' => $company->clients()->count(),
                'limit' => $plan->max_clients ?? 'Unlimited',
                'percent' => $plan && $plan->max_clients ? min(($company->clients()->count() / $plan->max_clients) * 100, 100) : 0
            ]
        ];

        return view('dashboard', compact('stats', 'recentInvoices', 'chartData', 'statusDistribution', 'subscriptionUsage', 'plan'));
    }
}
