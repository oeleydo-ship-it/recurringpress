<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * What a signed-in client sees. Every document comes from invoiceQuery()/quotationQuery(), limited
 * to this client and company; opening one hands over to the public document page, which already
 * covers the PDF, payment and accepting or rejecting. Drafts stay hidden until the tenant sends them.
 */
class DocumentController extends Controller
{
    private const PER_PAGE = 15;

    public function dashboard()
    {
        $open = $this->invoiceQuery()
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED])
            ->get()
            ->filter(fn (Invoice $i) => $i->remaining_amount > 0);

        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'outstanding' => (float) $open->sum(fn (Invoice $i) => $i->remaining_amount),
                'open_invoices' => $open->count(),
                'invoices' => $this->invoiceQuery()->count(),
                'quotations' => $this->quotationQuery()->count(),
            ],
            'invoices' => $this->invoiceQuery()->take(5)->get()->map(fn (Invoice $i) => $this->invoiceRow($i)),
            'quotations' => $this->quotationQuery()->take(5)->get()->map(fn (Quotation $q) => $this->quotationRow($q)),
        ]);
    }

    public function invoices()
    {
        return Inertia::render('Portal/Invoices', [
            'invoices' => $this->invoiceQuery()->paginate(self::PER_PAGE)->through(fn (Invoice $i) => $this->invoiceRow($i)),
        ]);
    }

    public function showInvoice(string $invoice)
    {
        return redirect()->route('invoices.public', $this->invoiceQuery()->findOrFail($invoice)->share_token);
    }

    public function invoicePdf(string $invoice)
    {
        return redirect()->route('invoices.public.download', $this->invoiceQuery()->findOrFail($invoice)->share_token);
    }

    public function quotations()
    {
        return Inertia::render('Portal/Quotations', [
            'quotations' => $this->quotationQuery()->paginate(self::PER_PAGE)->through(fn (Quotation $q) => $this->quotationRow($q)),
        ]);
    }

    public function showQuotation(string $quotation)
    {
        return redirect()->route('quotations.public', $this->quotationQuery()->findOrFail($quotation)->share_token);
    }

    public function quotationPdf(string $quotation)
    {
        return redirect()->route('quotations.public.download', $this->quotationQuery()->findOrFail($quotation)->share_token);
    }

    public function account()
    {
        return Inertia::render('Portal/Account');
    }

    public function updatePassword(Request $request)
    {
        $account = $this->clientAccount();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $account->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $account->forceFill(['password' => $validated['password']])->save();
        $request->session()->put(ClientAccount::SESSION_FINGERPRINT, $account->sessionFingerprint());

        return back()->with('success', 'Your password has been changed.');
    }

    private function clientAccount(): ClientAccount
    {
        return Auth::guard('client')->user();
    }

    /** Without global scopes: those follow a team login, which this browser may also hold. */
    private function invoiceQuery(): Builder
    {
        $account = $this->clientAccount();

        return Invoice::withoutGlobalScopes()
            ->where('company_id', $account->company_id)
            ->where('client_id', $account->client_id)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->latest('issue_date')
            ->latest('id');
    }

    private function quotationQuery(): Builder
    {
        $account = $this->clientAccount();

        return Quotation::withoutGlobalScopes()
            ->where('company_id', $account->company_id)
            ->where('client_id', $account->client_id)
            ->where('status', '!=', Quotation::STATUS_DRAFT)
            ->latest('issue_date')
            ->latest('id');
    }

    private function invoiceRow(Invoice $invoice): array
    {
        $unpaid = ! in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED], true);

        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'issue_date' => $invoice->issue_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $unpaid && $invoice->due_date?->isPast() ? Invoice::STATUS_OVERDUE : $invoice->status,
            'total' => (float) $invoice->total,
            'amount_due' => $unpaid ? (float) $invoice->remaining_amount : 0.0,
            'url' => route('portal.invoices.show', ['invoice' => $invoice->id]),
            'pdf_url' => route('portal.invoices.pdf', ['invoice' => $invoice->id]),
        ];
    }

    private function quotationRow(Quotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'number' => $quotation->quotation_number,
            'issue_date' => $quotation->issue_date?->toDateString(),
            'valid_until' => $quotation->valid_until?->toDateString(),
            'status' => $quotation->isExpired() && $quotation->status === Quotation::STATUS_SENT ? Quotation::STATUS_EXPIRED : $quotation->status,
            'total' => (float) $quotation->total,
            'url' => route('portal.quotations.show', ['quotation' => $quotation->id]),
            'pdf_url' => route('portal.quotations.pdf', ['quotation' => $quotation->id]),
        ];
    }
}
