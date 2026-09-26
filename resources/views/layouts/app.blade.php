@php
    $company = auth()->user()->company;
    $settings = $company->settings ?? null;
    $primaryColor = $settings->primary_color ?? '#0284c7';
    $secondaryColor = $settings->secondary_color ?? '#0ea5e9';
    $sidebarColor = $settings->sidebar_color ?? '#ffffff';
    $sidebarTextColor = $settings->sidebar_text_color ?? '#1f2937';
    $fontFamily = $settings->font_family ?? 'Inter';
    $fontSize = $settings->font_size ?? 'base';
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');

    $navGroups = [
        'Overview' => [
            ['route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'fa-grid-2', 'label' => 'Dashboard'],
        ],
        'Sales' => [
            ['route' => 'invoices.index', 'pattern' => 'invoices.*', 'icon' => 'fa-file-invoice', 'label' => 'Invoices'],
            ['route' => 'recurring-invoices.index', 'pattern' => 'recurring-invoices.*', 'icon' => 'fa-arrows-rotate', 'label' => 'Recurring Invoices'],
            ['route' => 'quotations.index', 'pattern' => 'quotations.*', 'icon' => 'fa-file-lines', 'label' => 'Quotations'],
            ['route' => 'clients.index', 'pattern' => 'clients.*', 'icon' => 'fa-users', 'label' => 'Clients'],
            ['route' => 'products.index', 'pattern' => 'products.*', 'icon' => 'fa-box', 'label' => 'Products'],
        ],
        'Finance' => [
            ['route' => 'payments.index', 'pattern' => 'payments.*', 'icon' => 'fa-credit-card', 'label' => 'Transactions'],
            ['route' => 'receipts.index', 'pattern' => 'receipts.*', 'icon' => 'fa-receipt', 'label' => 'Receipts'],
            ['route' => 'expenses.index', 'pattern' => 'expenses.*', 'icon' => 'fa-wallet', 'label' => 'Expenses'],
            ['route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'fa-chart-column', 'label' => 'Reports'],
        ],
    ];

    $showPayouts = auth()->user()->company->settings && auth()->user()->company->settings->payment_gateway_mode !== 'own';
    if ($showPayouts) {
        $navGroups['Finance'][] = ['route' => 'payouts.index', 'pattern' => 'payouts.*', 'icon' => 'fa-money-check-dollar', 'label' => 'Payouts'];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteName)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { "50": "#f0f9ff", "100": "#e0f2fe", "200": "#bae6fd", "300": "#7dd3fc", "400": "#38bdf8", "500": "#0ea5e9", "600": "#0284c7", "700": "#0369a1", "800": "#075985", "900": "#0c4a6e" }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;500;600;700&family=Lato:wght@300;400;700&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --primary-color: {{ $primaryColor }};
            --secondary-color: {{ $secondaryColor }};
            --sidebar-color: {{ $sidebarColor }};
            --sidebar-text-color: {{ $sidebarTextColor }};
            --sidebar-accent: color-mix(in srgb, {{ $primaryColor }} 45%, {{ $sidebarTextColor }});
            --font-family: '{{ $fontFamily }}', sans-serif;
        }
        body, html, input, select, textarea, button, p, span, div, h1, h2, h3, h4, h5, h6, a, label, th, td, li { font-family: '{{ $fontFamily }}', ui-sans-serif, system-ui, sans-serif !important; }
        .fas, .far, .fab, .fa, [class^="fa-"], [class*=" fa-"] { font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important; }
        @if($fontSize === 'small') html { font-size: 14px; } @elseif($fontSize === 'large') html { font-size: 18px; } @endif

        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-primary-hover:hover { background-color: var(--secondary-color) !important; }
        .text-primary { color: var(--primary-color) !important; }
        .border-primary { border-color: var(--primary-color) !important; }
        .bg-sidebar { background-color: var(--sidebar-color) !important; }
        .text-sidebar { color: var(--sidebar-text-color) !important; }
        .ring-primary:focus { --tw-ring-color: var(--primary-color) !important; }
        .focus\:ring-primary:focus { --tw-ring-color: var(--primary-color) !important; }
        .focus\:border-primary:focus { border-color: var(--primary-color) !important; }
        .bg-primary-light { background-color: color-mix(in srgb, var(--primary-color) 10%, white) !important; }
        .text-primary-dark { color: color-mix(in srgb, var(--primary-color) 80%, black) !important; }
        .text-primary-hover:hover { color: var(--secondary-color) !important; }
        .border-primary-hover:hover { border-color: var(--secondary-color) !important; }

        /* Sidebar nav item states. Every colour preset pairs a mid-tone primary with a very
           dark sidebar, so --primary-color alone is illegible as sidebar text. Accents are
           blended toward --sidebar-text-color, which keeps the brand hue while guaranteeing
           the blend moves away from --sidebar-color whichever direction the theme runs. */
        .bg-sidebar nav a.nav-item { color: color-mix(in srgb, var(--sidebar-text-color) 75%, transparent); border-left: 3px solid transparent; }
        .bg-sidebar nav a.nav-item:hover {
            background-color: color-mix(in srgb, var(--sidebar-text-color) 8%, transparent);
            color: var(--sidebar-text-color);
        }
        .bg-sidebar nav a.nav-item.nav-active {
            background-color: color-mix(in srgb, var(--sidebar-text-color) 12%, transparent);
            color: var(--sidebar-text-color);
            border-left-color: var(--sidebar-accent);
        }
        .bg-sidebar nav a.nav-item.nav-active i { color: var(--sidebar-accent); }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background-color: #9ca3af; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen antialiased">
    <div x-data="{ sidebarOpen: false }" class="flex min-h-screen">
        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 lg:hidden" @click="sidebarOpen = false">
            <div class="absolute inset-0 bg-gray-900/60"></div>
        </div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar shadow-xl transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:inset-auto lg:shadow-none lg:border-r lg:border-gray-200/70 flex flex-col">
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200/70 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 min-w-0">
                    @if($company->logo)
                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="h-8 w-auto max-w-[140px] object-contain">
                    @else
                        <span class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-building text-white text-sm"></i>
                        </span>
                        <span class="text-lg font-bold text-sidebar truncate">{{ Str::limit($company->name, 16) }}</span>
                    @endif
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-sidebar/70 hover:text-sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                @foreach($navGroups as $groupLabel => $items)
                    <div>
                        <h3 class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-sidebar/70">{{ $groupLabel }}</h3>
                        <div class="space-y-0.5">
                            @foreach($items as $item)
                                <a href="{{ route($item['route']) }}"
                                   class="nav-item flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-r-lg transition-colors {{ request()->routeIs($item['pattern']) ? 'nav-active' : '' }}">
                                    <i class="fas {{ $item['icon'] }} w-4 text-center"></i>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if(auth()->user()->isAdmin())
                    <div>
                        <h3 class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-sidebar/70">Management</h3>
                        <div class="space-y-0.5">
                            <a href="{{ route('users.index') }}"
                               class="nav-item flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-r-lg transition-colors {{ request()->routeIs('users.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-user-gear w-4 text-center"></i>
                                <span class="truncate">Team</span>
                            </a>
                            <a href="{{ route('settings.index') }}"
                               class="nav-item flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-r-lg transition-colors {{ request()->routeIs('settings.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-gear w-4 text-center"></i>
                                <span class="truncate">Settings</span>
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            <div class="flex-shrink-0 p-3 border-t border-gray-200/70">
                <a href="{{ route('profile') }}" class="flex items-center gap-2.5 rounded-lg p-2 hover:bg-black/5 transition-colors">
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm flex-shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-sidebar truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-sidebar/60 truncate">{{ auth()->user()->company->name ?? '' }}</p>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-200/70">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8 gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 -ml-1 p-1">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        @hasSection('page-title')
                            <div class="min-w-0">
                                <h1 class="text-base sm:text-lg font-semibold text-gray-900 truncate">@yield('page-title')</h1>
                                @hasSection('page-subtitle')
                                    <p class="text-xs text-gray-500 truncate hidden sm:block">@yield('page-subtitle')</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('invoices.create') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-primary bg-primary-hover text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                            <i class="fas fa-plus text-xs"></i>
                            New Invoice
                        </a>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 rounded-full hover:ring-2 hover:ring-gray-100 transition">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover">
                                <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block mr-1"></i>
                            </button>

                            <div x-show="open" x-cloak @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-gray-200 py-1.5 z-50 origin-top-right">
                                <div class="px-4 py-2 border-b border-gray-100 mb-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user w-4 text-gray-400"></i> Profile
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-gear w-4 text-gray-400"></i> Settings
                                    </a>
                                @endif
                                <hr class="my-1 border-gray-100">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-arrow-right-from-bracket w-4"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="max-w-[1600px] mx-auto w-full">
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </span>
                            <span class="flex-1 text-sm">{{ session('success') }}</span>
                            <button @click="show = false" class="text-green-600 hover:text-green-800"><i class="fas fa-times text-xs"></i></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation text-red-600 text-xs"></i>
                            </span>
                            <span class="flex-1 text-sm">{{ session('error') }}</span>
                            <button @click="show = false" class="text-red-600 hover:text-red-800"><i class="fas fa-times text-xs"></i></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-xs"></i>
                            </span>
                            <span class="flex-1 text-sm">{{ session('warning') }}</span>
                            <button @click="show = false" class="text-yellow-700 hover:text-yellow-900"><i class="fas fa-times text-xs"></i></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                            <div class="flex items-center gap-2.5 mb-2">
                                <i class="fas fa-exclamation-circle text-red-600"></i>
                                <span class="font-medium text-sm">Please check the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-0.5 ml-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @include('partials.tracking-and-livechat')
    @stack('scripts')
</body>
</html>
