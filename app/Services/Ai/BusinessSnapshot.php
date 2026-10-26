<?php

namespace App\Services\Ai;

use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\RecurringInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A compact, tenant-scoped picture of the business for AI analysis. Every query filters on
 * company_id explicitly (never relying on the auth-based global scope), and only names,
 * numbers and dates are included: no client emails, addresses, phones, tax or bank details.
 *
 * The forecast figures are computed here, not by the model, so the numbers shown in the UI
 * are exact; Claude only interprets them.
 */
class BusinessSnapshot
{
    private const OPEN = ['sent', 'viewed', 'partial', 'overdue'];

    public static function for(Company $company): array
    {
        $today = now()->startOfDay();
        $cid = $company->id;

        $open = Invoice::withoutGlobalScopes()->where('company_id', $cid)->whereIn('status', self::OPEN)
            ->with(['client' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderBy('due_date')->get();

        $openRows = $open->map(fn (Invoice $i) => [
            'number' => $i->invoice_number,
            'client' => $i->client?->bill_to_name,
            'status' => $i->status,
            'issued' => $i->issue_date?->toDateString(),
            'due' => $i->due_date?->toDateString(),
            'total' => round((float) $i->total, 2),
            'balance' => self::balance($i),
            'days_overdue' => $i->due_date && $i->due_date->lt($today) ? (int) $i->due_date->diffInDays($today) : 0,
        ]);

        $overdue = $openRows->where('days_overdue', '>', 0);

        return [
            'company' => $company->name,
            'currency' => $company->currency ?? 'USD',
            'today' => $today->toDateString(),
            'receivables' => [
                'open_count' => $openRows->count(),
                'open_balance' => round($openRows->sum('balance'), 2),
                'overdue_count' => $overdue->count(),
                'overdue_balance' => round($overdue->sum('balance'), 2),
                'aging' => [
                    '1_30_days' => round($overdue->whereBetween('days_overdue', [1, 30])->sum('balance'), 2),
                    '31_60_days' => round($overdue->whereBetween('days_overdue', [31, 60])->sum('balance'), 2),
                    '61_90_days' => round($overdue->whereBetween('days_overdue', [61, 90])->sum('balance'), 2),
                    'over_90_days' => round($overdue->where('days_overdue', '>', 90)->sum('balance'), 2),
                ],
                'draft_count' => Invoice::withoutGlobalScopes()->where('company_id', $cid)->where('status', 'draft')->count(),
                // Largest first: what matters most for collections.
                'open_invoices' => $openRows->sortByDesc('balance')->take(25)->values()->all(),
            ],
            'client_payment_behaviour' => self::clientBehaviour($cid),
            'recurring' => self::recurring($cid, $today),
            'forecast' => self::forecast($open, $cid, $today),
            'monthly' => self::monthly($cid),
            'quotations' => [
                'open_count' => Quotation::withoutGlobalScopes()->where('company_id', $cid)->whereIn('status', ['draft', 'sent'])->count(),
                'open_value' => round((float) Quotation::withoutGlobalScopes()->where('company_id', $cid)->whereIn('status', ['draft', 'sent'])->sum('total'), 2),
                'accepted_last_90_days' => Quotation::withoutGlobalScopes()->where('company_id', $cid)->where('status', 'accepted')->where('updated_at', '>=', now()->subDays(90))->count(),
            ],
        ];
    }

    /** Facts about one invoice and its client's history, for a payment reminder. */
    public static function forInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['client' => fn ($q) => $q->withoutGlobalScopes(), 'company']);
        $today = now()->startOfDay();

        $history = Invoice::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('client_id', $invoice->client_id)
            ->whereKeyNot($invoice->id)
            ->get();

        return [
            'company' => $invoice->company->name,
            'currency' => $invoice->company->currency ?? 'USD',
            'today' => $today->toDateString(),
            'invoice' => [
                'number' => $invoice->invoice_number,
                'issued' => $invoice->issue_date?->toDateString(),
                'due' => $invoice->due_date?->toDateString(),
                'total' => round((float) $invoice->total, 2),
                'paid' => round((float) $invoice->paid_amount, 2),
                'balance' => self::balance($invoice),
                'days_overdue' => $invoice->due_date && $invoice->due_date->lt($today) ? (int) $invoice->due_date->diffInDays($today) : 0,
                'status' => $invoice->status,
            ],
            'client' => [
                'name' => $invoice->client?->bill_to_name,
                'contact_first_name' => $invoice->client ? strtok((string) $invoice->client->name, ' ') : null,
                'other_invoices' => $history->count(),
                'other_paid' => $history->where('status', 'paid')->count(),
                'other_overdue' => $history->filter(fn ($i) => in_array($i->status, self::OPEN, true) && $i->due_date?->lt($today))->count(),
            ],
        ];
    }

    private static function balance(Invoice $i): float
    {
        return round(max(0, (float) $i->total - (float) $i->paid_amount), 2);
    }

    /** Per client: how late they usually pay (from completed payments vs due dates) and what they owe. */
    private static function clientBehaviour(int $cid): array
    {
        $payments = Payment::withoutGlobalScopes()
            ->where('payments.company_id', $cid)
            ->where('payments.status', 'completed')
            ->where('payments.payment_date', '>=', now()->subYear())
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->get(['payments.client_id', 'payments.payment_date', 'invoices.due_date']);

        $late = $payments->groupBy('client_id')->map(fn (Collection $rows) => round($rows->avg(
            fn ($r) => Carbon::parse($r->due_date)->startOfDay()->diffInDays(Carbon::parse($r->payment_date)->startOfDay(), false)
        ), 1));

        return Invoice::withoutGlobalScopes()->where('company_id', $cid)->whereIn('status', self::OPEN)
            ->with(['client' => fn ($q) => $q->withoutGlobalScopes()])
            ->get()
            ->groupBy('client_id')
            ->map(fn (Collection $rows, $clientId) => [
                'client' => $rows->first()->client?->bill_to_name,
                'owed' => round($rows->sum(fn ($i) => self::balance($i)), 2),
                'open_invoices' => $rows->count(),
                'avg_days_paid_after_due' => $late[$clientId] ?? null,
            ])
            ->sortByDesc('owed')->take(10)->values()->all();
    }

    private static function recurring(int $cid, Carbon $today): array
    {
        $all = RecurringInvoice::withoutGlobalScopes()->where('company_id', $cid)
            ->with(['client' => fn ($q) => $q->withoutGlobalScopes()])->get();
        $active = $all->where('status', RecurringInvoice::STATUS_ACTIVE);

        return [
            'active_count' => $active->count(),
            'paused_count' => $all->where('status', RecurringInvoice::STATUS_PAUSED)->count(),
            'cancelled_last_90_days' => $all->where('status', RecurringInvoice::STATUS_CANCELLED)->where('updated_at', '>=', now()->subDays(90))->count(),
            'monthly_recurring_revenue' => round($active->sum(fn ($r) => self::monthlyEquivalent($r)), 2),
            'upcoming_60_days' => $active
                ->flatMap(fn ($r) => collect(self::runs($r, $today, $today->copy()->addDays(60)))->map(fn ($d) => [
                    'schedule' => $r->name,
                    'client' => $r->client?->bill_to_name,
                    'date' => $d->toDateString(),
                    'amount' => round((float) $r->total, 2),
                ]))
                ->sortBy('date')->take(30)->values()->all(),
            'ending_within_60_days' => $active->filter(fn ($r) => $r->end_date && $r->end_date->between($today, $today->copy()->addDays(60)))
                ->map(fn ($r) => ['schedule' => $r->name, 'client' => $r->client?->bill_to_name, 'ends' => $r->end_date->toDateString()])->values()->all(),
        ];
    }

    /** Expected cash in: open balances by due date plus scheduled recurring invoices. */
    private static function forecast(Collection $open, int $cid, Carbon $today): array
    {
        $active = RecurringInvoice::withoutGlobalScopes()->where('company_id', $cid)->where('status', RecurringInvoice::STATUS_ACTIVE)->get();

        $window = function (int $days) use ($open, $active, $today) {
            $end = $today->copy()->addDays($days);
            $dueOpen = $open->filter(fn ($i) => $i->due_date && $i->due_date->gte($today) && $i->due_date->lte($end))->sum(fn ($i) => self::balance($i));
            $scheduled = $active->sum(fn ($r) => count(self::runs($r, $today, $end)) * (float) $r->total);

            return ['open_invoices_due' => round($dueOpen, 2), 'recurring_scheduled' => round($scheduled, 2), 'total' => round($dueOpen + $scheduled, 2)];
        };

        return [
            'overdue_balance' => round($open->filter(fn ($i) => $i->due_date && $i->due_date->lt($today))->sum(fn ($i) => self::balance($i)), 2),
            'next_30_days' => $window(30),
            'next_60_days' => $window(60),
            'next_90_days' => $window(90),
        ];
    }

    /** Last 6 months: collected, invoiced and expenses. */
    private static function monthly(int $cid): array
    {
        $from = now()->subMonthsNoOverflow(5)->startOfMonth();

        $collected = Payment::withoutGlobalScopes()->where('company_id', $cid)->where('status', 'completed')->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') ym, SUM(amount) total")->groupBy('ym')->pluck('total', 'ym');
        $invoiced = Invoice::withoutGlobalScopes()->where('company_id', $cid)->whereNotIn('status', ['draft', 'cancelled'])->where('issue_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') ym, SUM(total) total")->groupBy('ym')->pluck('total', 'ym');
        $expenses = Expense::withoutGlobalScopes()->where('company_id', $cid)->where('expense_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) total")->groupBy('ym')->pluck('total', 'ym');

        return collect(range(0, 5))->map(function ($i) use ($from, $collected, $invoiced, $expenses) {
            $ym = $from->copy()->addMonthsNoOverflow($i)->format('Y-m');

            return [
                'month' => $ym,
                'invoiced' => round((float) ($invoiced[$ym] ?? 0), 2),
                'collected' => round((float) ($collected[$ym] ?? 0), 2),
                'expenses' => round((float) ($expenses[$ym] ?? 0), 2),
            ];
        })->all();
    }

    private static function monthlyEquivalent(RecurringInvoice $r): float
    {
        $total = (float) $r->total;

        return match ($r->billing_cycle) {
            RecurringInvoice::CYCLE_WEEKLY => $total * 52 / 12,
            RecurringInvoice::CYCLE_QUARTERLY => $total / 3,
            RecurringInvoice::CYCLE_SEMI_ANNUAL => $total / 6,
            RecurringInvoice::CYCLE_ANNUAL => $total / 12,
            default => $total,
        };
    }

    /** Dates a schedule will generate an invoice in [$from, $to], stepping like getNextBillingDate(). */
    private static function runs(RecurringInvoice $r, Carbon $from, Carbon $to): array
    {
        $date = ($r->next_invoice_date ?? $r->start_date)?->copy();
        $dates = [];

        for ($n = 0; $date && $n < 60 && $date->lte($to); $n++) {
            if ($r->end_date && $date->gt($r->end_date)) {
                break;
            }
            if ($date->gte($from)) {
                $dates[] = $date->copy();
            }
            $date = match ($r->billing_cycle) {
                RecurringInvoice::CYCLE_WEEKLY => $date->addWeek(),
                RecurringInvoice::CYCLE_QUARTERLY => $date->addMonthsNoOverflow(3),
                RecurringInvoice::CYCLE_SEMI_ANNUAL => $date->addMonthsNoOverflow(6),
                RecurringInvoice::CYCLE_ANNUAL => $date->addYearNoOverflow(),
                default => $date->addMonthNoOverflow(),
            };
        }

        return $dates;
    }
}
