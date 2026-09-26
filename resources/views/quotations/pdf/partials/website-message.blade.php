@php
    $showQuotationMessage = ($quotation->company->settings->show_quotation_message ?? true) && !empty($quotation->company->settings->quotation_message ?? null);
    $showQuotationWebsite = ($quotation->company->settings->show_quotation_website ?? false) && !empty($quotation->company->website);
@endphp
@if($showQuotationMessage)
    <div style="margin-top: 15px; margin-bottom: 15px; font-size: 11px; color: #444;">
        {{ $quotation->company->settings->quotation_message }}
    </div>
@endif
@if($showQuotationWebsite)
    <div style="margin-top: 10px; font-size: 10px; color: #666;">
        {{ $quotation->company->website }}
    </div>
@endif
