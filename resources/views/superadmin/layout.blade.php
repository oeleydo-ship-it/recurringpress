<!DOCTYPE html>
<html lang="en">
@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Superadmin') - {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Quill WYSIWYG Editor (Free & Open Source) -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Quill Editor Styling */
        .ql-container {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }

        .ql-editor {
            min-height: 250px;
            padding: 20px;
            line-height: 1.8;
            color: #374151;
        }

        /* Headings */
        .ql-editor h1 {
            font-size: 2em;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.75em;
            margin-top: 1em;
            line-height: 1.3;
        }

        .ql-editor h2 {
            font-size: 1.5em;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.6em;
            margin-top: 1em;
            line-height: 1.35;
        }

        .ql-editor h3 {
            font-size: 1.25em;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5em;
            margin-top: 0.8em;
            line-height: 1.4;
        }

        /* Paragraphs */
        .ql-editor p {
            font-size: 1em;
            margin-bottom: 1em;
            line-height: 1.75;
        }

        /* Lists */
        .ql-editor ol,
        .ql-editor ul {
            padding-left: 1.5em;
            margin-bottom: 1em;
        }

        .ql-editor ol li,
        .ql-editor ul li {
            margin-bottom: 0.5em;
            padding-left: 0.25em;
        }

        .ql-editor ul li::marker {
            color: #0ea5e9;
        }

        .ql-editor ol li::marker {
            color: #374151;
            font-weight: 500;
        }

        /* Links */
        .ql-editor a {
            color: #0ea5e9;
            text-decoration: underline;
        }

        .ql-editor a:hover {
            color: #0284c7;
        }

        /* Bold, Italic, Underline */
        .ql-editor strong {
            font-weight: 600;
        }

        .ql-editor em {
            font-style: italic;
        }

        /* Blockquote */
        .ql-editor blockquote {
            border-left: 4px solid #0ea5e9;
            padding-left: 1em;
            margin: 1em 0;
            color: #4b5563;
            font-style: italic;
        }

        /* Images */
        .ql-editor img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1em 0;
        }

        /* Toolbar styling */
        .ql-toolbar {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            background: #f9fafb;
        }

        .ql-container {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>


<body class="bg-gray-100 min-h-screen">
    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-56 bg-gray-900 transform transition-transform duration-300 lg:translate-x-0 flex flex-col"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex items-center justify-between h-14 px-4 border-b border-gray-800 flex-shrink-0">
                <a href="{{ route('superadmin.dashboard') }}" class="flex items-center space-x-2">
                    <i class="fas fa-shield-alt text-sky-400 text-lg"></i>
                    <span class="text-white font-bold text-sm">Superadmin</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="px-3 py-4 space-y-1 overflow-y-auto flex-1">
                <a href="{{ route('superadmin.dashboard') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.dashboard') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-tachometer-alt w-4 text-xs"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="{{ route('superadmin.pages.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.pages.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-file-alt w-4 text-xs"></i>
                    <span class="ml-3">Pages</span>
                </a>
                <a href="{{ route('superadmin.users.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.users.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-users w-4 text-xs"></i>
                    <span class="ml-3">Users</span>
                </a>
                <a href="{{ route('superadmin.posts.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.posts.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-newspaper w-4 text-xs"></i>
                    <span class="ml-3">Blog Posts</span>
                </a>
                <a href="{{ route('superadmin.plans.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.plans.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-tags w-4 text-xs"></i>
                    <span class="ml-3">Plans</span>
                </a>
                <a href="{{ route('superadmin.payouts.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.payouts.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-money-check-alt w-4 text-xs"></i>
                    <span class="ml-3">Payouts</span>
                </a>
                <a href="{{ route('superadmin.reports.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.reports.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-chart-bar w-4 text-xs"></i>
                    <span class="ml-3">Reports</span>
                </a>
                <a href="{{ route('superadmin.menus.index') }}"
                    class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.menus.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-bars w-4 text-xs"></i>
                    <span class="ml-3">Menus</span>
                </a>

                <div class="pt-3 mt-3 border-t border-gray-800">
                    <p class="px-3 text-xs text-gray-500 uppercase tracking-wider mb-2">Settings</p>
                    <a href="{{ route('superadmin.settings.general') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.settings.general*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fas fa-cog w-4 text-xs"></i>
                        <span class="ml-3">General</span>
                    </a>
                    <a href="{{ route('superadmin.settings.stripe') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.settings.stripe*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fab fa-stripe w-4 text-xs"></i>
                        <span class="ml-3">Stripe</span>
                    </a>
                    <a href="{{ route('superadmin.settings.smtp') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.settings.smtp*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fas fa-envelope w-4 text-xs"></i>
                        <span class="ml-3">SMTP / Email</span>
                    </a>
                    <a href="{{ route('superadmin.settings.payouts') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.settings.payouts*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fas fa-percentage w-4 text-xs"></i>
                        <span class="ml-3">Payouts</span>
                    </a>
                    <a href="{{ route('superadmin.settings.landing') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.settings.landing*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fas fa-desktop w-4 text-xs"></i>
                        <span class="ml-3">Landing Page</span>
                    </a>
                    <a href="{{ route('superadmin.updates.index') }}"
                        class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('superadmin.updates.*') ? 'bg-sky-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <i class="fas fa-cloud-download-alt w-4 text-xs"></i>
                        <span class="ml-3">Updates</span>
                    </a>
                </div>
            </nav>

            <div class="p-3 border-t border-gray-800 bg-gray-900 flex-shrink-0">
                <div class="flex items-center mb-2">
                    <img src="{{ Auth::guard('superadmin')->user()->avatar_url }}" class="w-8 h-8 rounded-full">
                    <div class="ml-2 overflow-hidden">
                        <p class="text-white text-xs font-medium truncate">{{ Auth::guard('superadmin')->user()->name }}
                        </p>
                        <p class="text-gray-400 text-xs">Superadmin</p>
                    </div>
                </div>
                <form action="{{ route('superadmin.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-gray-800 rounded-lg text-xs">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </form>
                <div class="mt-2 pt-2 border-t border-gray-800 text-center">
                    <span class="text-gray-500 text-xs">
                        v{{ \App\Models\SystemSetting::get('app_version', config('installer.version', '1.0.0')) }}
                    </span>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col lg:ml-56">
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-900">@yield('title', 'Dashboard')</h1>
                <div></div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>