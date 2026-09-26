<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Auto Install - {{ $appName ?? config('app.name', 'Application') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .step-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .step-completed {
            background-color: #10b981;
            color: white;
        }

        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/10 backdrop-blur-lg mb-4">
                    <i class="fas fa-rocket text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-white">
                    {{ $appName ?? 'Application' }}
                </h1>
                <p class="text-purple-200 mt-2">Auto Installation Wizard</p>
            </div>

            <div class="mb-8">
                <div class="flex items-center justify-between">
                    @php
                        $steps = [
                            ['route' => 'autoinstall.detect', 'label' => 'Detect', 'icon' => 'search'],
                            ['route' => 'autoinstall.requirements', 'label' => 'Requirements', 'icon' => 'check-circle'],
                            ['route' => 'autoinstall.database', 'label' => 'Database', 'icon' => 'database'],
                            ['route' => 'autoinstall.application', 'label' => 'Settings', 'icon' => 'cog'],
                            ['route' => 'autoinstall.admin', 'label' => 'Admin', 'icon' => 'user-shield'],
                            ['route' => 'autoinstall.install', 'label' => 'Install', 'icon' => 'download'],
                            ['route' => 'autoinstall.complete', 'label' => 'Complete', 'icon' => 'flag-checkered'],
                        ];
                        $currentRoute = Route::currentRouteName();
                        $currentIndex = collect($steps)->search(fn($s) => $s['route'] === $currentRoute);
                    @endphp

                    @foreach($steps as $index => $step)
                        <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-full transition-all duration-300 
                                    {{ $index < $currentIndex ? 'step-completed' : ($index === $currentIndex ? 'step-active' : 'bg-white/20 text-white/60') }}">
                                @if($index < $currentIndex)
                                    <i class="fas fa-check text-sm"></i>
                                @else
                                    <i class="fas fa-{{ $step['icon'] }} text-sm"></i>
                                @endif
                            </div>
                            @if($index < count($steps) - 1)
                                <div class="flex-1 h-1 mx-2 {{ $index < $currentIndex ? 'bg-emerald-500' : 'bg-white/20' }}">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2">
                    @foreach($steps as $step)
                        <span class="text-xs text-purple-200 hidden sm:block">{{ $step['label'] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/10">
                @if(session('error'))
                    <div
                        class="mb-6 bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div
                        class="mb-6 bg-emerald-500/20 border border-emerald-500/30 text-emerald-200 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>

            <p class="text-center text-purple-300/60 text-sm mt-6">
                &copy; {{ date('Y') }} {{ $appName ?? 'Application' }}. Powered by AutoInstaller
            </p>
        </div>
    </div>

    @stack('scripts')
</body>

</html>