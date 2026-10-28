<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $company['brand'] }}">
    <title>{{ $company['name'] }} — Billing portal</title>
    <style>
        :root { color-scheme: light; --brand: {{ $company['brand'] }}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #172033; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.6; }
        .accent { height: 6px; background: var(--brand); }
        .wrap { width: min(100% - 40px, 960px); margin: auto; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 30px 0; }
        .identity { display: flex; align-items: center; gap: 14px; font-weight: 700; }
        .logo { max-width: 150px; max-height: 48px; object-fit: contain; }
        .monogram { display: grid; place-items: center; width: 46px; height: 46px; border-radius: 14px; background: var(--brand); color: #fff; font-size: 22px; }
        a { color: var(--brand); text-underline-offset: 4px; overflow-wrap: anywhere; }
        .signin { color: #334155; font-size: 14px; font-weight: 600; white-space: nowrap; }
        main { padding: 48px 0 60px; }
        .eyebrow { margin: 0 0 16px; color: var(--brand); font-size: 12px; font-weight: 750; letter-spacing: .14em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 740px; font-size: clamp(32px, 6vw, 56px); line-height: 1.12; letter-spacing: -.04em; overflow-wrap: anywhere; }
        .intro { max-width: 620px; margin: 24px 0 38px; color: #64748b; font-size: 18px; }
        .grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px; }
        .card { padding: 30px; border: 1px solid #e2e8f0; border-radius: 20px; background: white; box-shadow: 0 6px 24px #17203304; }
        .icon { display: inline-grid; place-items: center; width: 44px; height: 44px; margin-bottom: 18px; border-radius: 12px; color: var(--brand); background: #f1f5f9; }
        h2 { margin: 0 0 10px; font-size: 19px; letter-spacing: -.02em; }
        p { margin: 0 0 16px; }
        .muted { color: #64748b; font-size: 15px; }
        .detail { margin-top: 18px; font-size: 14px; }
        .label { display: block; color: #64748b; font-size: 12px; margin-bottom: 3px; }
        footer { padding: 24px 0 36px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        a:focus-visible { outline: 3px solid var(--brand); outline-offset: 5px; border-radius: 3px; }
        @media (max-width: 640px) { main { padding-top: 24px; } .grid { grid-template-columns: 1fr; } .card { padding: 24px; } header { align-items: flex-start; } }
    </style>
</head>
<body>
<div class="accent"></div>
<div class="wrap">
    <header>
        <div class="identity">
            @if ($company['logo_url'])
                <img class="logo" src="{{ $company['logo_url'] }}" alt="{{ $company['name'] }} logo">
            @else
                <span class="monogram" aria-hidden="true">{{ mb_substr($company['name'], 0, 1) }}</span>
            @endif
            <span>{{ $company['name'] }}</span>
        </div>
        <a class="signin" href="{{ $loginUrl }}">Team sign in <span aria-hidden="true">↗</span></a>
    </header>
    <main>
        <p class="eyebrow">Billing portal</p>
        <h1>Welcome to {{ $company['name'] }}.</h1>
        <p class="intro">Your place to view invoices, review quotations, and find payment details shared by our team.</p>
        <div class="grid">
            <section class="card" aria-labelledby="documents-title">
                <div class="icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                </div>
                <h2 id="documents-title">Looking for your invoice?</h2>
                <p class="muted">Open the invoice or quotation link in the email we sent you. Each document has its own private link, so there is no client account to create.</p>
                <p class="muted">Can't find your link? Contact our team and we'll help you get it.</p>
            </section>
            <section class="card" aria-labelledby="contact-title">
                <h2 id="contact-title">Get in touch</h2>
                <p class="muted">Questions about a document or payment? We're here to help.</p>
                @if ($company['email'])
                    <div class="detail"><span class="label">Email</span><a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></div>
                @endif
                @if ($company['phone'])
                    <div class="detail"><span class="label">Phone</span><a href="tel:{{ $company['phone'] }}">{{ $company['phone'] }}</a></div>
                @endif
                @if ($company['website'] && in_array(strtolower((string) parse_url($company['website'], PHP_URL_SCHEME)), ['https', 'http'], true))
                    <div class="detail"><span class="label">Website</span><a href="{{ $company['website'] }}" target="_blank" rel="noopener noreferrer">Visit our website <span aria-hidden="true">↗</span></a></div>
                @endif
                @if (! $company['email'] && ! $company['phone'])
                    <p class="muted">Reply to the email that included your document link to reach our team.</p>
                @endif
            </section>
        </div>
    </main>
    <footer>{{ $company['name'] }} billing portal @unless ($company['white_label']) · Powered by {{ $siteName }} @endunless</footer>
</div>
</body>
</html>
