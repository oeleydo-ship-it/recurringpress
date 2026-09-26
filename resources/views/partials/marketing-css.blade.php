{{-- Shared design system for the marketing site and the auth (guest) pages.
     Included by layouts/landing.blade.php and layouts/guest.blade.php so the two
     cannot drift apart. Edit tokens here and every page follows. --}}
<style>
        /* ==========================================================================
           Design tokens — single source of truth for the marketing site.
           Adjust here and every page follows.
           ========================================================================== */
        :root {
            --ink-900: #0B1220;
            --ink-800: #1B2637;
            --ink-700: #334155;
            --ink-600: #4B5A6E;
            --ink-500: #6B7A90;
            --ink-400: #94A3B8;

            --line: #E8ECF2;
            --line-strong: #D8E0EA;

            --surface: #FFFFFF;
            --surface-2: #F7F9FC;

            --brand-50: #F0F9FF;
            --brand-100: #E0F2FE;
            --brand-200: #BAE6FD;
            --brand-500: #0EA5E9;
            --brand-600: #0284C7;
            --brand-700: #0369A1;

            --ok-50: #ECFDF5;
            --ok-500: #10B981;
            --ok-600: #059669;

            --r-sm: 10px;
            --r: 14px;
            --r-lg: 20px;
            --r-xl: 28px;

            /* Layered, low-opacity shadows read as "premium"; single big blurs read as dated. */
            --sh-xs: 0 1px 2px rgba(11, 18, 32, .04);
            --sh-sm: 0 1px 2px rgba(11, 18, 32, .04), 0 2px 6px -2px rgba(11, 18, 32, .06);
            --sh-card: 0 1px 2px rgba(11, 18, 32, .04), 0 10px 26px -14px rgba(11, 18, 32, .12);
            --sh-lift: 0 2px 4px rgba(11, 18, 32, .04), 0 20px 44px -18px rgba(11, 18, 32, .18);
            --sh-brand: 0 8px 20px -8px rgba(14, 165, 233, .5);

            --header-h: 72px;
            --section-y: clamp(3.25rem, 5.5vw, 5rem);
        }

        /* ---------- base ---------- */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: calc(var(--header-h) + 1rem);
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink-600);
            background: var(--surface);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-feature-settings: 'cv11', 'ss01';
        }

        ::selection {
            background: var(--brand-200);
            color: var(--brand-700);
        }

        [x-cloak] {
            display: none !important;
        }

        :focus-visible {
            outline: 2px solid var(--brand-500);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ---------- layout ---------- */
        .wrap {
            max-width: 80rem;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        .wrap-md {
            max-width: 64rem;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        .wrap-sm {
            max-width: 48rem;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        @media (min-width: 640px) {
            .wrap,
            .wrap-md,
            .wrap-sm {
                padding-inline: 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .wrap,
            .wrap-md,
            .wrap-sm {
                padding-inline: 2rem;
            }
        }

        .section {
            padding-block: var(--section-y);
        }

        .section-sm {
            padding-block: clamp(2.5rem, 4vw, 3.5rem);
        }

        .band {
            background: var(--surface-2);
            border-block: 1px solid var(--line);
        }

        .rule {
            border: 0;
            border-top: 1px solid var(--line);
        }

        /* ---------- typography ----------
           Fluid scale with negative tracking on the large sizes: the single biggest
           lever on how "designed" a marketing page feels. */
        .display,
        .h1,
        .h2,
        .h3 {
            color: var(--ink-900);
            text-wrap: balance;
        }

        /* Hero headline. Caps at 44px — large enough to lead the page without
           swallowing it on wide monitors. */
        .display {
            font-size: clamp(1.875rem, 1.15rem + 1.9vw, 2.75rem);
            line-height: 1.1;
            letter-spacing: -0.03em;
            font-weight: 800;
        }

        .h1 {
            font-size: clamp(1.5rem, 1.05rem + 1.15vw, 2.125rem);
            line-height: 1.18;
            letter-spacing: -0.025em;
            font-weight: 700;
        }

        .h2 {
            font-size: clamp(1.3125rem, 1rem + 0.75vw, 1.625rem);
            line-height: 1.25;
            letter-spacing: -0.022em;
            font-weight: 700;
        }

        .h3 {
            font-size: 1rem;
            line-height: 1.45;
            letter-spacing: -0.008em;
            font-weight: 700;
        }

        .lead {
            font-size: clamp(0.9375rem, 0.9rem + 0.22vw, 1.0625rem);
            line-height: 1.65;
            color: var(--ink-600);
            text-wrap: pretty;
        }

        .body-sm {
            font-size: 0.875rem;
            line-height: 1.62;
            color: var(--ink-600);
        }

        .kicker {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--brand-600);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3125rem 0.75rem;
            border-radius: 999px;
            background: var(--brand-50);
            border: 1px solid var(--brand-200);
            color: var(--brand-700);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .eyebrow i {
            font-size: 0.6875rem;
        }

        /* ---------- buttons ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.0625rem;
            border-radius: var(--r);
            font-weight: 600;
            font-size: 0.875rem;
            line-height: 1;
            border: 1px solid transparent;
            transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .18s;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-lg {
            padding: 0.8125rem 1.375rem;
            font-size: 0.9375rem;
            border-radius: var(--r);
        }

        .btn-primary {
            background: var(--brand-500);
            color: #fff;
            box-shadow: var(--sh-brand);
        }

        .btn-primary:hover {
            background: var(--brand-600);
        }

        .btn-ghost {
            background: var(--surface);
            border-color: var(--line-strong);
            color: var(--ink-800);
            box-shadow: var(--sh-xs);
        }

        .btn-ghost:hover {
            background: var(--surface-2);
            border-color: var(--ink-400);
        }

        .btn-white {
            background: #fff;
            color: var(--brand-700);
            box-shadow: var(--sh-sm);
        }

        .btn-white:hover {
            background: var(--brand-50);
        }

        .btn-quiet {
            color: var(--ink-700);
            padding-inline: 0.5rem;
        }

        .btn-quiet:hover {
            color: var(--ink-900);
        }

        /* ---------- cards ---------- */
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            box-shadow: var(--sh-card);
            overflow: hidden;
        }

        .card-hover {
            transition: box-shadow .22s, transform .22s, border-color .22s;
        }

        .card-hover:hover {
            box-shadow: var(--sh-lift);
            border-color: var(--line-strong);
            transform: translateY(-3px);
        }

        .card-flat {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
        }

        .accent-bar {
            height: 3px;
            width: 100%;
        }

        .icon-tile {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: var(--r-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-tile-lg {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: var(--r);
        }

        /* ---------- decorative canvas ----------
           A masked dot grid plus one soft brand glow. Replaces the blurred colour
           blobs — same warmth, far less noise. */
        .canvas {
            position: relative;
            isolation: isolate;
            overflow: hidden;
        }

        .canvas::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            background-image: radial-gradient(var(--line-strong) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 0%, #000 35%, transparent 100%);
            mask-image: radial-gradient(ellipse 75% 65% at 50% 0%, #000 35%, transparent 100%);
            opacity: .6;
        }

        .canvas::after {
            content: "";
            position: absolute;
            z-index: -1;
            top: -45%;
            left: 50%;
            translate: -50% 0;
            width: min(1100px, 130%);
            aspect-ratio: 2 / 1;
            background: radial-gradient(ellipse at center, rgba(14, 165, 233, .15), rgba(14, 165, 233, 0) 65%);
            pointer-events: none;
        }

        /* ---------- CTA panel ---------- */
        .cta-panel {
            position: relative;
            overflow: hidden;
            border-radius: var(--r-xl);
            padding: clamp(2.5rem, 5vw, 3.75rem);
            text-align: center;
            background:
                radial-gradient(ellipse 80% 120% at 15% 0%, rgba(255, 255, 255, .22), transparent 60%),
                linear-gradient(135deg, var(--brand-600) 0%, var(--brand-500) 55%, #38BDF8 100%);
            box-shadow: 0 24px 60px -28px rgba(2, 132, 199, .65);
        }

        .cta-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .16) 1px, transparent 1px);
            background-size: 20px 20px;
            -webkit-mask-image: radial-gradient(ellipse 60% 80% at 80% 100%, #000, transparent 70%);
            mask-image: radial-gradient(ellipse 60% 80% at 80% 100%, #000, transparent 70%);
            pointer-events: none;
        }

        .cta-panel>* {
            position: relative;
            z-index: 1;
        }

        /* ---------- header ---------- */
        .site-header {
            position: fixed;
            inset-inline: 0;
            top: 0;
            z-index: 50;
            height: var(--header-h);
            background: rgba(255, 255, 255, .8);
            backdrop-filter: saturate(180%) blur(12px);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid transparent;
            transition: border-color .2s, box-shadow .2s, background-color .2s;
        }

        .site-header[data-scrolled="true"] {
            border-bottom-color: var(--line);
            box-shadow: var(--sh-xs);
            background: rgba(255, 255, 255, .92);
        }

        .site-main {
            padding-top: var(--header-h);
        }

        .navlink {
            position: relative;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--ink-600);
            padding-block: 0.5rem;
            transition: color .16s;
        }

        .navlink:hover {
            color: var(--ink-900);
        }

        .navlink[aria-current="page"] {
            color: var(--ink-900);
            font-weight: 600;
        }

        .navlink[aria-current="page"]::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -0.125rem;
            height: 2px;
            border-radius: 2px;
            background: var(--brand-500);
        }

        .navlink-mobile {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: var(--r-sm);
            font-weight: 500;
            color: var(--ink-700);
        }

        .navlink-mobile:hover {
            background: var(--surface-2);
            color: var(--ink-900);
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            font-size: 1.125rem;
            color: var(--ink-900);
        }

        /* ---------- footer ---------- */
        .foot-link {
            font-size: 0.9375rem;
            color: var(--ink-600);
            transition: color .16s;
        }

        .foot-link:hover {
            color: var(--brand-600);
        }

        .foot-head {
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ink-900);
        }

        .social-dot {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink-500);
            transition: color .16s, border-color .16s, background-color .16s;
        }

        .social-dot:hover {
            color: var(--brand-600);
            border-color: var(--brand-200);
            background: var(--brand-50);
        }

        /* ---------- forms ---------- */
        .field {
            width: 100%;
            padding: 0.75rem 0.9375rem;
            background: var(--surface-2);
            border: 1px solid var(--line-strong);
            border-radius: var(--r);
            color: var(--ink-900);
            font-size: 0.9375rem;
            transition: border-color .16s, box-shadow .16s, background-color .16s;
        }

        .field::placeholder {
            color: var(--ink-400);
        }

        .field:focus {
            outline: none;
            background: var(--surface);
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px var(--brand-100);
        }

        .label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--ink-800);
            margin-bottom: 0.4375rem;
        }

        /* ---------- prose (CMS pages & blog posts) ---------- */
        .prose {
            color: var(--ink-600);
            line-height: 1.75;
        }

        .prose>*:first-child {
            margin-top: 0;
        }

        .prose h1 {
            font-size: 2.25em;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--ink-900);
            margin-top: 0;
            margin-bottom: .8em;
            line-height: 1.1;
        }

        .prose h2 {
            font-size: 1.5em;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--ink-900);
            margin-top: 2em;
            margin-bottom: .75em;
            line-height: 1.25;
        }

        .prose h3 {
            font-size: 1.25em;
            font-weight: 700;
            color: var(--ink-800);
            margin-top: 1.6em;
            margin-bottom: .6em;
            line-height: 1.4;
        }

        .prose p {
            margin-bottom: 1.25em;
        }

        .prose-lg p {
            font-size: 1.0625rem;
        }

        .prose ul,
        .prose ol {
            margin-block: 1em;
            padding-left: 1.625em;
        }

        .prose li {
            margin-block: .5em;
        }

        .prose ul>li::marker {
            color: var(--brand-500);
        }

        .prose ol>li::marker {
            color: var(--ink-500);
            font-weight: 600;
        }

        .prose a {
            color: var(--brand-600);
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 500;
        }

        .prose a:hover {
            color: var(--brand-700);
        }

        .prose strong {
            color: var(--ink-900);
            font-weight: 600;
        }

        .prose blockquote {
            border-left: 3px solid var(--brand-500);
            padding: .25em 0 .25em 1.125em;
            margin-block: 1.6em;
            color: var(--ink-700);
            font-style: italic;
        }

        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: var(--r-lg);
            border: 1px solid var(--line);
            margin-block: 2em;
        }

        .prose hr {
            border: 0;
            border-top: 1px solid var(--line);
            margin-block: 3em;
        }

        .prose code {
            background: var(--surface-2);
            border: 1px solid var(--line);
            padding: .15em .375em;
            border-radius: 6px;
            font-size: .875em;
        }

        .prose pre {
            background: var(--ink-900);
            color: #E2E8F0;
            padding: 1.125em;
            border-radius: var(--r);
            overflow-x: auto;
            margin-block: 1.5em;
        }

        .prose pre code {
            background: none;
            border: 0;
            padding: 0;
            color: inherit;
        }

        .prose table {
            width: 100%;
            border-collapse: collapse;
            margin-block: 2em;
            font-size: .9375rem;
        }

        .prose :is(th, td) {
            border: 1px solid var(--line);
            padding: .75em 1em;
            text-align: left;
        }

        .prose th {
            background: var(--surface-2);
            font-weight: 600;
            color: var(--ink-900);
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
</style>
