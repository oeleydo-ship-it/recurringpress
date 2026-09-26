<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Product;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $dateRange = $request->get('date_range', 'this_month');
        $dates = $this->getDateRange($dateRange);

        // Invoice Summary
        $invoiceStats = [
            'total' => Invoice::count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'pending' => Invoice::whereIn('status', ['sent', 'viewed'])->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
            'draft' => Invoice::where('status', 'draft')->count(),
        ];

        $invoiceAmounts = [
            'total' => Invoice::sum('total'),
            'paid' => Invoice::where('status', 'paid')->sum('total'),
            'outstanding' => Invoice::whereIn('status', ['sent', 'viewed', 'overdue'])->sum('total'),
        ];

        // Period Stats
        $periodInvoices = Invoice::whereBetween('issue_date', [$dates['start'], $dates['end']])->count();
        $periodRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$dates['start'], $dates['end']])
            ->sum('total');

        // Payment Stats
        $totalPayments = Payment::where('status', 'completed')->sum('amount');
        $periodPayments = Payment::where('status', 'completed')
            ->whereBetween('payment_date', [$dates['start'], $dates['end']])
            ->sum('amount');

        // Client Stats
        $totalClients = Client::count();
        $newClients = Client::whereBetween('created_at', [$dates['start'], $dates['end']])->count();

        // Top Clients by Revenue
        $topClients = Invoice::where('status', 'paid')
            ->select('client_id', DB::raw('SUM(total) as total_revenue'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('client_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->with('client:id,name,email')
            ->get();

        // Revenue by Month (last 12 months)
        $revenueByMonth = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(paid_at) as year'),
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Invoices by Status (for pie chart data)
        $invoicesByStatus = [
            ['status' => 'Paid', 'count' => $invoiceStats['paid'], 'color' => '#10b981'],
            ['status' => 'Pending', 'count' => $invoiceStats['pending'], 'color' => '#f59e0b'],
            ['status' => 'Overdue', 'count' => $invoiceStats['overdue'], 'color' => '#ef4444'],
            ['status' => 'Draft', 'count' => $invoiceStats['draft'], 'color' => '#6b7280'],
        ];

        // Payment Methods Breakdown
        $paymentMethods = Payment::where('status', 'completed')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Expenses
        $totalExpenses = Expense::sum(DB::raw('amount + tax_amount'));
        $periodExpenses = Expense::whereBetween('expense_date', [$dates['start'], $dates['end']])
            ->sum(DB::raw('amount + tax_amount'));

        $expensesByCategory = Expense::whereBetween('expense_date', [$dates['start'], $dates['end']])
            ->select('category', DB::raw('SUM(amount + tax_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->label = Expense::categories()[$row->category] ?? ucfirst($row->category);
                return $row;
            });

        $expensesByMonth = Expense::where('expense_date', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(expense_date) as year'),
                DB::raw('MONTH(expense_date) as month'),
                DB::raw('SUM(amount + tax_amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        // Profit (period)
        $periodProfit = (float) $periodRevenue - (float) $periodExpenses;
        $profitMargin = $periodRevenue > 0 ? ($periodProfit / $periodRevenue) * 100 : 0;

        // Top Products
        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', 'paid')
            ->select('invoice_items.name', DB::raw('SUM(invoice_items.quantity) as quantity_sold'), DB::raw('SUM(invoice_items.quantity * invoice_items.price) as revenue'))
            ->groupBy('invoice_items.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return view('reports.index', compact(
            'dateRange',
            'invoiceStats',
            'invoiceAmounts',
            'periodInvoices',
            'periodRevenue',
            'totalPayments',
            'periodPayments',
            'totalClients',
            'newClients',
            'topClients',
            'revenueByMonth',
            'invoicesByStatus',
            'paymentMethods',
            'topProducts',
            'totalExpenses',
            'periodExpenses',
            'expensesByCategory',
            'expensesByMonth',
            'periodProfit',
            'profitMargin'
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
            case 'this_quarter':
                return ['start' => now()->startOfQuarter(), 'end' => now()->endOfQuarter()];
            case 'last_quarter':
                return ['start' => now()->subQuarter()->startOfQuarter(), 'end' => now()->subQuarter()->endOfQuarter()];
            case 'this_year':
                return ['start' => now()->startOfYear(), 'end' => now()->endOfYear()];
            case 'last_year':
                return ['start' => now()->subYear()->startOfYear(), 'end' => now()->subYear()->endOfYear()];
            default:
                return ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()];
        }
    }
}
