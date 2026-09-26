@extends('autoinstall.layout')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-white mb-2">Application Settings</h2>
        <p class="text-purple-200 mb-6">Configure your application name and URL.</p>

        <form action="{{ route('autoinstall.setup-application') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="app_name">
                    <i class="fas fa-tag mr-1"></i> Application Name
                </label>
                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $appName ?? '') }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="My Application">
                @error('app_name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="app_url">
                    <i class="fas fa-globe mr-1"></i> Application URL
                </label>
                <input type="url" name="app_url" id="app_url" value="{{ old('app_url', request()->root()) }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="https://yourdomain.com">
                @error('app_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-purple-300/60 text-sm mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    This should be the full URL where your application will be accessed.
                </p>
            </div>

            <div class="mt-8 flex justify-between items-center">
                <a href="{{ route('autoinstall.database') }}"
                    class="inline-flex items-center px-6 py-2 text-purple-300 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </a>

                <button type="submit"
                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300">
                    Continue
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </form>
    </div>
@endsection