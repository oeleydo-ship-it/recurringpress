@if(($invoice->company->settings->show_authorized_signature ?? false) && $invoice->company->authorized_signature)
    <div style="margin-top: 30px; text-align: right;">
        <img src="{{ public_path('storage/' . $invoice->company->authorized_signature) }}" style="max-height: 50px; max-width: 180px; object-fit: contain; margin-bottom: 6px;" alt="Signature">
        <div style="border-top: 1px solid #333; width: 200px; margin-left: auto; padding-top: 4px; font-size: 10px; color: #555;">Authorized Signature</div>
    </div>
@endif
