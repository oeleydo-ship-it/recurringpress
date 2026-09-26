@extends('autoinstall.layout')

@section('content')
    <div class="text-center">
        <div
            class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 mb-6 animate-pulse">
            <i class="fas fa-check text-5xl text-white"></i>
        </div>

        <h2 class="text-3xl font-bold text-white mb-4">Installation Complete!</h2>

        <p class="text-purple-200 mb-8 max-w-md mx-auto">
            Congratulations! <strong class="text-white">{{ $appName }}</strong> has been successfully installed
            and is ready to use.
        </p>

        <div class="bg-white/5 rounded-lg p-6 mb-8 text-left max-w-md mx-auto">
            <h3 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-clipboard-list mr-2 text-purple-400"></i>
                What's Next?
            </h3>
            <ul class="space-y-3">
                <li class="flex items-start text-purple-200">
                    <i class="fas fa-sign-in-alt text-emerald-400 mr-3 mt-1"></i>
                    <span>Log in with your administrator account</span>
                </li>
                <li class="flex items-start text-purple-200">
                    <i class="fas fa-cog text-emerald-400 mr-3 mt-1"></i>
                    <span>Configure your application settings</span>
                </li>
                <li class="flex items-start text-purple-200">
                    <i class="fas fa-users text-emerald-400 mr-3 mt-1"></i>
                    <span>Start adding users and content</span>
                </li>
            </ul>
        </div>

        <div
            class="bg-amber-500/20 border border-amber-500/30 text-amber-200 px-4 py-3 rounded-lg mb-8 max-w-md mx-auto text-left">
            <div class="flex items-start">
                <i class="fas fa-shield-alt mr-3 mt-1"></i>
                <div>
                    <strong class="text-amber-100">Security Reminder</strong>
                    <p class="text-sm mt-1">
                        For security, the installation manifest has been archived.
                        Make sure to keep your admin credentials safe.
                    </p>
                </div>
            </div>
        </div>

        <a href="{{ $redirect ?? '/login' }}"
            class="inline-flex items-center px-10 py-4 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white font-bold rounded-lg shadow-lg hover:from-emerald-600 hover:to-cyan-600 transition-all duration-300 transform hover:scale-105 text-lg">
            <i class="fas fa-arrow-right mr-3"></i>
            Go to Application
        </a>

        <p class="text-purple-300/60 text-sm mt-8">
            <i class="fas fa-clock mr-1"></i>
            Installed on {{ now()->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
@endsection