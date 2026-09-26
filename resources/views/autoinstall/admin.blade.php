@extends('autoinstall.layout')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-white mb-2">Administrator Account</h2>
        <p class="text-purple-200 mb-6">Create the first administrator account for your application.</p>

        <form action="{{ route('autoinstall.setup-admin') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="name">
                        <i class="fas fa-user mr-1"></i> Full Name
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="John Doe" required>
                    @error('name')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="email">
                        <i class="fas fa-envelope mr-1"></i> Email Address
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="admin@example.com" required>
                    @error('email')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="company_name">
                    <i class="fas fa-building mr-1"></i> Company Name
                </label>
                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Your Company Name" required>
                @error('company_name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="password">
                        <i class="fas fa-lock mr-1"></i> Password
                    </label>
                    <input type="password" name="password" id="password"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="••••••••" required>
                    @error('password')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="password_confirmation">
                        <i class="fas fa-lock mr-1"></i> Confirm Password
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="••••••••" required>
                </div>
            </div>

            <p class="text-purple-300/60 text-sm">
                <i class="fas fa-shield-alt mr-1"></i>
                Password must be at least 8 characters long.
            </p>

            <div class="mt-8 flex justify-between items-center">
                <a href="{{ route('autoinstall.application') }}"
                    class="inline-flex items-center px-6 py-2 text-purple-300 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </a>

                <button type="submit"
                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300">
                    Continue to Installation
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </form>
    </div>
@endsection