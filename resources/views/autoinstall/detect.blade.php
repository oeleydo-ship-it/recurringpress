@extends('autoinstall.layout')

@section('content')
    <div class="text-center">
        <div
            class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 mb-6">
            <i class="fas fa-box-open text-4xl text-white"></i>
        </div>

        <h2 class="text-2xl font-bold text-white mb-4">Installation Detected</h2>

        <p class="text-purple-200 mb-6">
            We've detected that <strong class="text-white">{{ $appName }}</strong>
            <span class="text-sm text-purple-300">(v{{ $appVersion }})</span>
            is ready for installation.
        </p>

        @if(!empty($manifest['description']))
            <div class="bg-white/5 rounded-lg p-4 mb-6 text-left">
                <p class="text-purple-200 text-sm">{{ $manifest['description'] }}</p>
            </div>
        @endif

        <div class="bg-white/5 rounded-lg p-6 mb-8 text-left">
            <h3 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-list-check mr-2 text-purple-400"></i>
                Installation will include:
            </h3>
            <ul class="space-y-3">
                <li class="flex items-center text-purple-200">
                    <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                    Server requirements verification
                </li>
                <li class="flex items-center text-purple-200">
                    <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                    Database configuration & migration
                </li>
                <li class="flex items-center text-purple-200">
                    <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                    Application settings setup
                </li>
                <li class="flex items-center text-purple-200">
                    <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                    Administrator account creation
                </li>
            </ul>
        </div>

        <a href="{{ route('autoinstall.requirements') }}"
            class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-play mr-2"></i>
            Start Installation
        </a>
    </div>
@endsection