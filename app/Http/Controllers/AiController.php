<?php

namespace App\Http\Controllers;

use App\Jobs\SendDocumentEmail;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use App\Services\Ai\AiFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tenant AI endpoints (JSON). Each request counts against the company's monthly quota;
 * failures come back as 422 with a message safe to show.
 */
class AiController extends Controller
{
    public function __construct(private AiFeatures $features)
    {
    }

    public function insights(): JsonResponse
    {
        $company = auth()->user()->company;

        return $this->run(fn () => self::withInvoiceLinks($this->features->insights($company), $company));
    }

    public function reminder(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate(['tone' => ['required', Rule::in(AiFeatures::TONES)]]);
        abort_unless(self::remindable($invoice), 422, 'This invoice has nothing outstanding.');

        return $this->run(fn () => $this->features->reminder($invoice, $request->input('tone')));
    }

    /** Send the (edited) reminder to the client through the company's mailer, with the PDF. */
    public function sendReminder(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:5000',
        ]);
        abort_unless(self::remindable($invoice), 422, 'This invoice has nothing outstanding.');

        $invoice->loadMissing(['client' => fn ($q) => $q->withoutGlobalScopes()]);

        if (blank($invoice->client?->email)) {
            return back()->with('error', 'This client has no email address.');
        }

        SendDocumentEmail::dispatch($invoice, reminder: $validated);

        return back()->with('success', 'Reminder queued to ' . $invoice->client->email . '.');
    }

    public function lineItems(Request $request): JsonResponse
    {
        $request->validate(['description' => 'required|string|min:3|max:1000']);

        return $this->run(fn () => $this->features->lineItems(auth()->user()->company, $request->input('description')));
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1|max:20',
            'messages.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'messages.*.content' => 'required|string|max:2000',
        ]);

        return $this->run(fn () => $this->features->chat(auth()->user()->company, $validated['messages']));
    }

    private static function remindable(Invoice $invoice): bool
    {
        return in_array($invoice->status, ['sent', 'viewed', 'partial', 'overdue'], true)
            && (float) $invoice->total - (float) $invoice->paid_amount > 0;
    }

    /** Turn invoice numbers in suggested actions into links (this company's invoices only). */
    public static function withInvoiceLinks(?array $insights, Company $company): ?array
    {
        if (! $insights) {
            return null;
        }

        // Invoice numbers are only unique per company, so always scope the lookup explicitly.
        $numbers = collect($insights['analysis']['actions'] ?? [])->pluck('invoice_numbers')->flatten()->filter()->unique()->values();
        $ids = $numbers->isEmpty() ? collect() : Invoice::withoutGlobalScopes()->where('company_id', $company->id)
            ->whereIn('invoice_number', $numbers)->pluck('id', 'invoice_number');

        $insights['analysis']['actions'] = collect($insights['analysis']['actions'] ?? [])->map(fn ($a) => [
            ...$a,
            'invoices' => collect($a['invoice_numbers'] ?? [])->filter(fn ($n) => $ids->has($n))
                ->map(fn ($n) => ['number' => $n, 'url' => route('invoices.show', $ids[$n])])->values()->all(),
        ])->all();

        return $insights;
    }

    private function run(callable $fn): JsonResponse
    {
        @set_time_limit(150);

        try {
            $data = $fn();
        } catch (AiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            ...$data,
            'remaining' => AiClient::remaining(auth()->user()->company),
        ]);
    }
}
