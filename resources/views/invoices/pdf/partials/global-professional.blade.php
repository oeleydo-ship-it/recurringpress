@php
    $seller = $invoice->company;
    $buyer = $invoice->client;
    $settings = $seller->settings;
    $currency = strtoupper($seller->currency ?: 'USD');
    $money = fn ($amount) => $currency . ' ' . number_format((float) $amount, 2);
    $country = strtolower(trim((string) $seller->country));
    $taxIdFor = fn ($value) => match (strtolower(trim((string) $value))) {
        'united arab emirates', 'uae', 'ae' => 'TRN',
        'philippines', 'ph', 'phl' => 'TIN',
        default => 'Tax ID',
    };
    $taxLabel = in_array($country, ['united arab emirates', 'uae', 'ae', 'philippines', 'ph', 'phl'], true) ? 'VAT / tax' : 'Tax';
    $discount = $invoice->discount_type === 'percentage' ? (float) $invoice->subtotal * (float) $invoice->discount_amount / 100 : (float) $invoice->discount_amount;
    $address = fn ($party) => collect([$party?->address, collect([$party?->city, $party?->state, $party?->postal_code])->filter()->implode(', '), $party?->country])->filter()->all();
    $paid = (float) ($invoice->paid_amount ?? 0);
    $balance = max(0, (float) $invoice->total - $paid);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 14mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #26313e; font-family: 'DejaVu Sans', sans-serif; font-size: 10px; line-height: 1.5; }
        .page { padding: 14px 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .masthead { padding-bottom: 22px; border-bottom: 3px solid {{ $accent }}; margin-bottom: 22px; }
        .masthead td:last-child { text-align: right; }
        .company-name { font-size: 19px; font-weight: bold; color: {{ $accent }}; }
        .logo { max-height: 48px; max-width: 165px; margin-bottom: 7px; }
        .eyebrow { font-size: 8px; letter-spacing: 1.8px; text-transform: uppercase; color: #697586; margin-bottom: 7px; }
        .title { font-size: 29px; letter-spacing: 1px; font-weight: bold; color: {{ $accent }}; line-height: 1.1; }
        .number { margin-top: 9px; font-size: 11px; font-weight: bold; }
        .status { display: inline-block; font-size: 8px; padding: 3px 8px; margin-top: 9px; border: 1px solid {{ $accent }}; color: {{ $accent }}; text-transform: uppercase; letter-spacing: 1px; }
        .parties { margin-bottom: 22px; table-layout: fixed; }
        .parties td { width: 50%; padding-right: 25px; overflow-wrap: break-word; word-wrap: break-word; }
        .parties td:last-child { padding-right: 0; padding-left: 20px; }
        .party-name { font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        .party-line { color: #526071; font-size: 9px; }
        .tax-id { margin-top: 7px; font-size: 9px; font-weight: bold; }
        .meta { margin: 0 0 22px; background: {{ $tint }}; table-layout: fixed; }
        .meta td { padding: 12px; width: 25%; border-right: 2px solid white; }
        .meta td:last-child { border-right: 0; }
        .meta .eyebrow { font-size: 7px; letter-spacing: 1px; margin-bottom: 5px; }
        .meta-value { font-weight: bold; font-size: 10px; word-wrap: break-word; }
        .items { table-layout: fixed; margin-bottom: 18px; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { background: {{ $accent }}; color: white; padding: 10px 7px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; }
        .items td { padding: 9px 7px; border-bottom: 1px solid #dfe5eb; word-wrap: break-word; }
        .items .right, .right { text-align: right; }
        .item-name { font-weight: bold; }
        .description { color: #697586; margin-top: 4px; font-size: 9px; white-space: pre-line; }
        .summary { margin-top: 12px; page-break-inside: avoid; }
        .payment { width: 52%; padding-right: 30px; }
        .totals { width: 48%; }
        .totals td { padding: 6px 9px; font-size: 10px; }
        .totals td:last-child { text-align: right; }
        .totals .grand td { padding-top: 11px; padding-bottom: 11px; background: {{ $tint }}; color: {{ $accent }}; font-weight: bold; border-top: 2px solid {{ $accent }}; font-size: 12px; }
        .totals .balance td { padding-top: 10px; font-weight: bold; color: {{ $accent }}; }
        .section-title { font-size: 8px; letter-spacing: 1.2px; text-transform: uppercase; color: {{ $accent }}; font-weight: bold; margin-bottom: 8px; }
        .payment p { margin: 4px 0; font-size: 9px; color: #526071; word-wrap: break-word; }
        .notes { margin-top: 18px; padding-top: 14px; border-top: 1px solid #dfe5eb; }
        .note-block { margin-bottom: 10px; }
        .note-text { font-size: 9px; color: #526071; white-space: pre-line; word-wrap: break-word; }
        .footer { margin-top: 14px; border-top: 2px solid {{ $accent }}; padding-top: 11px; font-size: 8px; color: #697586; page-break-inside: avoid; }
        .gulf .masthead { border-top: 7px solid {{ $accent }}; padding-top: 22px; border-bottom: 1px solid #dfe5eb; }
        .gulf .parties td:last-child { border-left: 2px solid {{ $accent }}; }
        .manila .title { font-size: 25px; letter-spacing: 3px; }
        .manila .masthead { border-bottom: 5px double {{ $accent }}; }
        .manila .meta { background: white; border: 1px solid {{ $accent }}; }
        .manila .meta td { border-right: 1px solid #dfe5eb; }
        .manila .items th { background: white; color: {{ $accent }}; border-top: 2px solid {{ $accent }}; border-bottom: 2px solid {{ $accent }}; }
        .manila .items td { border-left: 1px solid #dfe5eb; border-right: 1px solid #dfe5eb; }
        .international .masthead { border-bottom: 1px solid #263545; }
        .international .title { font-size: 34px; font-weight: normal; letter-spacing: 4px; }
        .international .items th { background: #f0f3f6; color: {{ $accent }}; border-bottom: 1px solid #263545; }
        .international .meta { background: white; border-top: 1px solid #dfe5eb; border-bottom: 1px solid #dfe5eb; }
        .international .meta td { padding-left: 0; }
        .international .totals .grand td { background: white; }
        .executive { font-family: 'DejaVu Serif', serif; }
        .executive .masthead { border-top: 1px solid {{ $accent }}; border-bottom: 1px solid {{ $accent }}; padding-top: 20px; }
        .executive .company-name { font-size: 21px; }
        .executive .title { font-size: 24px; letter-spacing: 3px; }
        .executive .meta { background: white; border-bottom: 1px solid {{ $accent }}; }
        .executive .items th { background: {{ $tint }}; color: {{ $accent }}; border-bottom: 1px solid {{ $accent }}; }
        .executive .totals .grand td { background: {{ $accent }}; color: white; }
        .commerce .masthead { background: {{ $accent }}; padding: 22px; border: 0; }
        .commerce .company-name, .commerce .title, .commerce .number { color: white; }
        .commerce .status { border-color: white; color: white; }
        .commerce .items th, .commerce .items td { border: 1px solid #dfe5eb; }
        .commerce .items th { border-color: {{ $accent }}; }
        .commerce .totals { border: 1px solid #dfe5eb; }
    </style>
</head>
<body>
<div class="page {{ $design }}">
    <div class="masthead">
        <table><tr><td style="width:58%">
            @if (($settings->show_logo_on_invoice ?? true) && $seller->logo_data_uri)<img class="logo" src="{{ $seller->logo_data_uri }}" alt="Company logo">@endif
            <div class="company-name">{{ $seller->name }}</div>
        </td><td style="width:42%">
            <div class="title">INVOICE</div>
            <div class="number">{{ $invoice->invoice_number }}</div>
            @if ($invoice->status === 'paid')<span class="status">Paid in full</span>@elseif ($invoice->status === 'cancelled')<span class="status">Cancelled</span>@endif
        </td></tr></table>
    </div>
    <table class="parties"><tr><td>
        <div class="eyebrow">Issued by</div>
        <div class="party-name">{{ $seller->name }}</div>
        @foreach ($address($seller) as $line)<div class="party-line">{{ $line }}</div>@endforeach
        @if ($seller->email)<div class="party-line">{{ $seller->email }}</div>@endif
        @if ($seller->phone)<div class="party-line">{{ $seller->phone }}</div>@endif
        @if ($seller->tax_number)<div class="tax-id">{{ $taxIdFor($seller->country) }}: {{ $seller->tax_number }}</div>@endif
    </td><td>
        <div class="eyebrow">Bill to</div>
        <div class="party-name">{{ $buyer?->bill_to_name ?? 'Customer' }}</div>
        @if ($buyer?->bill_to_contact)<div class="party-line">{{ $buyer->bill_to_contact }}</div>@endif
        @foreach ($address($buyer) as $line)<div class="party-line">{{ $line }}</div>@endforeach
        @if ($buyer?->email)<div class="party-line">{{ $buyer->email }}</div>@endif
        @if ($buyer?->tax_number)<div class="tax-id">{{ $taxIdFor($buyer->country ?: $seller->country) }}: {{ $buyer->tax_number }}</div>@endif
    </td></tr></table>
    <table class="meta"><tr>
        <td><div class="eyebrow">Issue date</div><div class="meta-value">{{ $invoice->issue_date?->format('d M Y') ?? '—' }}</div></td>
        <td><div class="eyebrow">Due date</div><div class="meta-value">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div></td>
        <td><div class="eyebrow">Reference</div><div class="meta-value">{{ $invoice->reference ?: $invoice->invoice_number }}</div></td>
        <td><div class="eyebrow">Invoice currency</div><div class="meta-value">{{ $currency }}</div></td>
    </tr></table>
    <table class="items">
        <thead><tr>
            <th style="width:36%">Description</th><th class="right" style="width:12%">Quantity</th><th class="right" style="width:18%">Unit price</th><th class="right" style="width:14%">Tax rate</th><th class="right" style="width:20%">Amount</th>
        </tr></thead>
        <tbody>@foreach ($invoice->items as $item)<tr>
            <td><div class="item-name">{{ $item->name }}</div>@if ($item->description)<div class="description">{{ $item->description }}</div>@endif</td>
            <td class="right">{{ number_format((float) $item->quantity, 2) }}@if ($item->unit)<div class="description">{{ $item->unit }}</div>@endif</td>
            <td class="right">{{ number_format((float) $item->price, 2) }}</td>
            <td class="right">{{ number_format((float) $item->tax_rate, 2) }}%</td>
            <td class="right"><strong>{{ number_format((float) $item->subtotal, 2) }}</strong></td>
        </tr>@endforeach</tbody>
    </table>
    <table class="summary"><tr><td class="payment">
        @if ($settings->show_payment_instructions ?? false)
            <div class="section-title">Payment information</div>
            @if ($settings->bank_name)<p>{{ $settings->bank_name }}</p>@endif
            @if ($settings->bank_account_name)<p>Account name: {{ $settings->bank_account_name }}</p>@endif
            @if ($settings->bank_account_number)<p>Account / IBAN: {{ $settings->bank_account_number }}</p>@endif
            @if ($settings->bank_routing_number)<p>SWIFT / routing: {{ $settings->bank_routing_number }}</p>@endif
            @if ($settings->payment_instructions)<p style="white-space:pre-line">{{ $settings->payment_instructions }}</p>@endif
        @else
            <div class="section-title">Payment reference</div><p>Please quote {{ $invoice->invoice_number }} when making payment.</p>
        @endif
    </td><td class="totals"><table>
        <tr><td>Subtotal</td><td>{{ $money($invoice->subtotal) }}</td></tr>
        <tr><td>{{ $taxLabel }}</td><td>{{ $money($invoice->tax_amount) }}</td></tr>
        @if ($discount > 0)<tr><td>Discount @if ($invoice->discount_type === 'percentage') ({{ number_format((float) $invoice->discount_amount, 2) }}%)@endif</td><td>-{{ $money($discount) }}</td></tr>@endif
        <tr class="grand"><td>Invoice total</td><td>{{ $money($invoice->total) }}</td></tr>
        @if ($paid > 0)<tr><td>Amount paid</td><td>{{ $money($paid) }}</td></tr>@endif
        <tr class="balance"><td>Balance due</td><td>{{ $money($balance) }}</td></tr>
    </table></td></tr></table>
    @if ($invoice->notes || $invoice->terms)<div class="notes">
        @if ($invoice->notes)<div class="note-block"><div class="section-title">Notes</div><div class="note-text">{{ $invoice->notes }}</div></div>@endif
        @if ($invoice->terms)<div class="note-block"><div class="section-title">Terms &amp; conditions</div><div class="note-text">{{ $invoice->terms }}</div></div>@endif
    </div>@endif
    @include('invoices.pdf.partials.signature')
    @if ($settings->show_invoice_footer ?? true)<div class="footer">{{ $seller->invoice_footer ?: 'Thank you for your business.' }}</div>@endif
</div>
</body>
</html>

