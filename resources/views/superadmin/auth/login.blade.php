<!DOCTYPE html>
<html lang="en">
@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Login - {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-sky-500 rounded-2xl mb-4">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Superadmin Login</h1>
            <p class="text-gray-400 mt-2">Access the administration panel</p>
        </div>

        <div class="bg-gray-800 rounded-2xl p-8">
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('superadmin.login') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember"
                        class="w-4 h-4 text-sky-500 border-gray-600 rounded focus:ring-sky-500 bg-gray-700">
                    <label for="remember" class="ml-2 text-sm text-gray-300">Remember me</label>
                </div>
                <button type="submit"
                    class="w-full py-3 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            <a href="{{ route('landing') }}" class="hover:text-gray-300">&larr; Back to website</a>
        </p>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>

</html>