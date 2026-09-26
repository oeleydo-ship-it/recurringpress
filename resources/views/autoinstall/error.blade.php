@extends('autoinstall.layout')

@section('content')
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/10 mb-6">
            <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
        </div>

        <h2 class="text-2xl font-bold text-white mb-2">{{ $title ?? 'Error' }}</h2>
        <p class="text-purple-200 mb-8">{{ $message ?? 'An unexpected error occurred.' }}</p>

        <div class="flex justify-center space-x-4">
            <a href="{{ route('landing') }}"
                class="px-6 py-2 bg-white/10 text-white hover:bg-white/20 rounded-lg transition">
                Return to Home
            </a>
            <a href="{{ route('install.index') }}"
                class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition">
                Try Again
            </a>
        </div>
    </div>
@endsection