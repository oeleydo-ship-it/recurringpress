@extends('superadmin.layout')

@section('title', 'SMTP Settings')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">SMTP / Email Settings</h1>
            <p class="text-gray-600">Configure how the application sends emails (invoices, quotations, registration).</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <form action="{{ route('superadmin.settings.smtp.update') }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Mailer -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mailer</label>
                        <select name="mail_mailer"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="smtp" {{ old('mail_mailer', $settings['mail_mailer']) == 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ old('mail_mailer', $settings['mail_mailer']) == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="log" {{ old('mail_mailer', $settings['mail_mailer']) == 'log' ? 'selected' : '' }}>
                                Log (Testing)</option>
                        </select>
                    </div>

                    <!-- Host & Port -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}"
                            placeholder="smtp.example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Port</label>
                        <input type="text" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}"
                            placeholder="587"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <!-- Username & Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Username</label>
                        <input type="text" name="mail_username"
                            value="{{ old('mail_username', $settings['mail_username']) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Password</label>
                        <input type="password" name="mail_password"
                            value="{{ old('mail_password', $settings['mail_password']) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <!-- Encryption -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                        <select name="mail_encryption"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="null" {{ old('mail_encryption', $settings['mail_encryption']) == null ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    <!-- From Address & Name -->
                    <div class="md:col-span-2 border-t pt-6 mt-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Sender Identity</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">From Address</label>
                                <input type="email" name="mail_from_address"
                                    value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                                    placeholder="noreply@yourdomain.com"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                                <input type="text" name="mail_from_name"
                                    value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                                    placeholder="{{ config('app.name') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-8 pt-6 border-t">
                    <button type="submit"
                        class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                        Save Email Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Test Email Section -->
        <div class="mt-8 bg-white rounded-xl shadow-sm overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Test Email Configuration</h3>
            <p class="text-sm text-gray-500 mb-4">Send a test email to verify your settings are working correctly.</p>

            @if(session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('superadmin.settings.smtp.test') }}" method="POST" class="flex gap-4 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Send To</label>
                    <input type="email" name="test_email" required placeholder="example@domain.com"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <button type="submit"
                    class="px-6 py-2 bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-900 transition">
                    Send Test Email
                </button>
            </form>
        </div>
    </div>
@endsection