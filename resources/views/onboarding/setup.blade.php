@extends('layouts.onboarding')

@section('title', 'Setup Your Company - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('content')
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto" x-data="setupWizard()">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Setup Your Company</h1>
                <p class="text-white/80">Complete these steps to get the most out of
                    {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="flex justify-center mb-8 overflow-x-auto pb-2">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="flex items-center">
                            <button @click="goToStep(index + 1)" :class="{
                                                    'bg-green-500 text-white': completedSteps.includes(index + 1),
                                                    'bg-white text-sky-600': currentStep === index + 1 && !completedSteps.includes(index + 1),
                                                    'bg-white/20 text-white/60': currentStep !== index + 1 && !completedSteps.includes(index + 1)
                                                }"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300 focus:outline-none">
                                <template x-if="completedSteps.includes(index + 1)">
                                    <i class="fas fa-check"></i>
                                </template>
                                <template x-if="!completedSteps.includes(index + 1)">
                                    <span x-text="index + 1"></span>
                                </template>
                            </button>
                            <template x-if="index < steps.length - 1">
                                <div :class="completedSteps.includes(index + 1) ? 'bg-green-400' : 'bg-white/30'"
                                    class="w-6 sm:w-12 h-1 mx-1 sm:mx-2 rounded transition-all duration-300"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl card-shadow overflow-hidden">
                <!-- Step Title Bar -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900" x-text="steps[currentStep - 1].title"></h2>
                            <p class="text-sm text-gray-500" x-text="steps[currentStep - 1].description"></p>
                        </div>
                        <span class="text-sm text-gray-400" x-text="'Step ' + currentStep + ' of ' + steps.length"></span>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="p-6" x-ref="stepContent">
                    <!-- Step 1: Company Logo -->
                    <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-md mx-auto text-center">
                            <!-- Current Logo Preview -->
                            <div class="mb-6">
                                <div
                                    class="w-32 h-32 mx-auto rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300">
                                    @if($company->logo)
                                        <img src="{{ $company->logo_url }}" alt="Logo" class="w-full h-full object-contain"
                                            x-ref="logoPreview">
                                    @else
                                        <div class="text-center" x-ref="logoPlaceholder">
                                            <i class="fas fa-image text-gray-400 text-4xl mb-2"></i>
                                            <p class="text-xs text-gray-500">No logo uploaded</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <input type="file" id="logo" accept="image/*" class="hidden" @change="handleLogoUpload($event)"
                                x-ref="logoInput">
                            <label for="logo"
                                class="inline-flex items-center px-6 py-3 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg cursor-pointer transition">
                                <i class="fas fa-upload mr-2"></i>
                                Choose Logo Image
                            </label>
                            <p class="text-sm text-gray-500 mt-3">PNG, JPG up to 2MB. Recommended: 200x200px</p>
                        </div>
                    </div>

                    <!-- Step 2: Company Address -->
                    <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                                <textarea x-model="formData.address" rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="123 Business Street">{{ $company->address }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" x-model="formData.city" value="{{ $company->city }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="New York">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                                <input type="text" x-model="formData.state" value="{{ $company->state }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="NY">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                <input type="text" x-model="formData.postal_code" value="{{ $company->postal_code }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="10001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <input type="text" x-model="formData.country" value="{{ $company->country }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="United States">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" x-model="formData.phone" value="{{ $company->phone }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                    placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment Gateway -->
                    <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-2xl mx-auto">
                            <p class="text-gray-600 mb-6">Choose which payment methods your clients can use to pay invoices.
                            </p>

                            <div class="space-y-4">
                                <!-- Stripe -->
                                <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition"
                                    :class="formData.accept_stripe ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="checkbox" x-model="formData.accept_stripe"
                                        class="mt-1 mr-4 w-5 h-5 text-sky-500 rounded focus:ring-sky-500">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <i class="fab fa-stripe text-2xl text-purple-600 mr-3"></i>
                                            <span class="font-medium text-gray-900">Stripe</span>
                                            <span
                                                class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Recommended</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Accept credit cards, debit cards, and more</p>
                                    </div>
                                </label>

                                <!-- Stripe Gateway Mode (shown when Stripe is selected) -->
                                <div x-show="formData.accept_stripe" x-transition
                                    class="ml-9 pl-4 border-l-2 border-purple-200 space-y-4">
                                    <p class="text-sm font-medium text-gray-700">Choose your Stripe setup:</p>

                                    <!-- App's Payment Gateway -->
                                    <label class="flex items-start p-3 border rounded-lg cursor-pointer transition"
                                        :class="formData.payment_gateway_mode === 'app' ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'">
                                        <input type="radio" x-model="formData.payment_gateway_mode" value="app"
                                            class="mt-0.5 mr-3 w-4 h-4 text-sky-500 focus:ring-sky-500">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-cloud text-sky-500 mr-2"></i>
                                                <span class="font-medium text-gray-900 text-sm">Use App's Payment
                                                    Gateway</span>
                                                <span
                                                    class="ml-2 px-2 py-0.5 bg-sky-100 text-sky-700 text-xs rounded-full">Easy
                                                    Setup</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Use the platform's Stripe account for
                                                payment processing. No API keys needed.</p>
                                        </div>
                                    </label>

                                    <!-- Own Stripe Account -->
                                    <label class="flex items-start p-3 border rounded-lg cursor-pointer transition"
                                        :class="formData.payment_gateway_mode === 'own' ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'">
                                        <input type="radio" x-model="formData.payment_gateway_mode" value="own"
                                            class="mt-0.5 mr-3 w-4 h-4 text-sky-500 focus:ring-sky-500">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-key text-purple-500 mr-2"></i>
                                                <span class="font-medium text-gray-900 text-sm">Use Your Own Stripe
                                                    Account</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Connect your own Stripe account to receive
                                                payments directly.</p>
                                        </div>
                                    </label>

                                    <!-- Stripe API Keys (shown when "own" is selected) -->
                                    <div x-show="formData.payment_gateway_mode === 'own'" x-transition
                                        class="space-y-3 pt-2">
                                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="flex items-start">
                                                <i class="fas fa-info-circle text-amber-500 mt-0.5 mr-2"></i>
                                                <p class="text-xs text-amber-700">Find your API keys in your <a
                                                        href="https://dashboard.stripe.com/apikeys" target="_blank"
                                                        class="underline font-medium">Stripe Dashboard</a>.</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Publishable
                                                Key</label>
                                            <input type="text" x-model="formData.stripe_key"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500"
                                                placeholder="pk_live_...">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Secret
                                                Key</label>
                                            <input type="password" x-model="formData.stripe_secret"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500"
                                                placeholder="sk_live_...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Transfer -->
                                <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition"
                                    :class="formData.accept_bank_transfer ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="checkbox" x-model="formData.accept_bank_transfer"
                                        class="mt-1 mr-4 w-5 h-5 text-sky-500 rounded focus:ring-sky-500">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <i class="fas fa-university text-2xl text-blue-600 mr-3"></i>
                                            <span class="font-medium text-gray-900">Bank Transfer</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Direct bank/wire transfers</p>
                                    </div>
                                </label>

                                <!-- Bank Details (shown when bank transfer is selected) -->
                                <div x-show="formData.accept_bank_transfer" x-transition
                                    class="ml-9 pl-4 border-l-2 border-sky-200 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                                            <input type="text" x-model="formData.bank_name"
                                                value="{{ $settings->bank_name }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                                            <input type="text" x-model="formData.bank_account_name"
                                                value="{{ $settings->bank_account_name }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Account
                                                Number</label>
                                            <input type="text" x-model="formData.bank_account_number"
                                                value="{{ $settings->bank_account_number }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Cash -->
                                <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition"
                                    :class="formData.accept_cash ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="checkbox" x-model="formData.accept_cash"
                                        class="mt-1 mr-4 w-5 h-5 text-sky-500 rounded focus:ring-sky-500">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <i class="fas fa-money-bill-wave text-2xl text-green-600 mr-3"></i>
                                            <span class="font-medium text-gray-900">Cash / Check</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Accept cash or check payments</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Tax Settings -->
                    <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-md mx-auto">
                            <!-- Enable Tax Toggle -->
                            <label class="flex items-center justify-between p-4 border-2 rounded-xl cursor-pointer mb-6"
                                :class="formData.tax_enabled ? 'border-sky-500 bg-sky-50' : 'border-gray-200'">
                                <div>
                                    <span class="font-medium text-gray-900">Enable Tax on Invoices</span>
                                    <p class="text-sm text-gray-500">Add tax to your invoice line items</p>
                                </div>
                                <div class="relative">
                                    <input type="checkbox" x-model="formData.tax_enabled" class="sr-only">
                                    <div :class="formData.tax_enabled ? 'bg-sky-500' : 'bg-gray-300'"
                                        class="w-12 h-6 rounded-full transition"></div>
                                    <div :class="formData.tax_enabled ? 'translate-x-6' : 'translate-x-0'"
                                        class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition">
                                    </div>
                                </div>
                            </label>

                            <div x-show="formData.tax_enabled" x-transition class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Tax Rate (%)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" x-model="formData.default_tax_rate"
                                            value="{{ $settings->default_tax_rate ?? 0 }}"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                            placeholder="10.00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2">Common rates: VAT (20%), GST (10%), Sales Tax
                                        (varies)</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Number / VAT ID
                                        (Optional)</label>
                                    <input type="text" x-model="formData.tax_number" value="{{ $company->tax_number }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                        placeholder="e.g., GB123456789">
                                    <p class="text-sm text-gray-500 mt-2">This will appear on your invoices</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Theme & Invoice Template -->
                    <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-2xl mx-auto">
                            <!-- Primary Color -->
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Brand Color</label>
                                <div class="flex flex-wrap gap-3">
                                    @foreach(['#0ea5e9' => 'Sky Blue', '#6366f1' => 'Indigo', '#8b5cf6' => 'Purple', '#ec4899' => 'Pink', '#ef4444' => 'Red', '#f97316' => 'Orange', '#eab308' => 'Yellow', '#22c55e' => 'Green', '#14b8a6' => 'Teal', '#64748b' => 'Slate'] as $color => $name)
                                        <button type="button" @click="formData.primary_color = '{{ $color }}'"
                                            :class="formData.primary_color === '{{ $color }}' ? 'ring-2 ring-offset-2 ring-gray-400 scale-110' : ''"
                                            class="w-10 h-10 rounded-lg transition transform hover:scale-105"
                                            style="background-color: {{ $color }}" title="{{ $name }}"></button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Invoice Template -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Invoice Template</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach(\App\Models\CompanySetting::invoiceTemplates() as $key => $template)
                                        <button type="button" @click="formData.invoice_template = '{{ $key }}'"
                                            :class="formData.invoice_template === '{{ $key }}' ? 'ring-2 ring-sky-500 scale-[1.02]' : 'border-gray-200 hover:border-gray-300 hover:scale-[1.02]'"
                                            class="group relative border rounded-xl overflow-hidden bg-white shadow-sm transition-all duration-200 text-left">

                                            <div class="aspect-[21/29.7] w-full bg-gray-100 relative overflow-hidden">
                                                <x-template-thumbnail :src="route('settings.template.preview', $key)" />

                                                <!-- Selection Overlay -->
                                                <div class="absolute inset-0 bg-sky-900/5 opacity-0 transition-opacity"
                                                    :class="formData.invoice_template === '{{ $key }}' ? 'opacity-100' : ''">
                                                </div>

                                                <!-- Selected Indicator -->
                                                <div class="absolute top-2 right-2 transition-transform duration-200"
                                                    :class="formData.invoice_template === '{{ $key }}' ? 'scale-100' : 'scale-0'">
                                                    <div
                                                        class="bg-sky-500 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-md">
                                                        <i class="fas fa-check text-xs"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3 border-t border-gray-100 bg-white">
                                                <span
                                                    class="text-sm font-medium text-gray-900 block">{{ $template['name'] }}</span>
                                                <span
                                                    class="text-xs text-gray-500 block truncate">{{ $template['description'] }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Add Team Members -->
                    <div x-show="currentStep === 6" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-md mx-auto">
                            <div class="text-center mb-6">
                                <div
                                    class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user-plus text-sky-500 text-2xl"></i>
                                </div>
                                <p class="text-gray-600">Invite team members to help manage invoices and clients.</p>
                            </div>

                            @if($teamMembers->count() > 0)
                                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Current Team Members</h4>
                                    <ul class="space-y-2">
                                        @foreach($teamMembers as $member)
                                            <li class="flex items-center text-sm text-gray-600">
                                                <img src="{{ $member->avatar_url }}" class="w-8 h-8 rounded-full mr-3">
                                                <span>{{ $member->name }}</span>
                                                <span class="ml-auto text-gray-400">{{ ucfirst($member->role) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Team Member Name</label>
                                    <input type="text" x-model="formData.team_name"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                        placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" x-model="formData.team_email"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                        placeholder="john@company.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <select x-model="formData.team_role"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                        <option value="admin">Admin - Full access</option>
                                        <option value="member">Member - Limited access</option>
                                    </select>
                                </div>
                            </div>

                            <p class="text-sm text-gray-500 mt-4 text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                You can add more team members later from Settings → Team
                            </p>
                        </div>
                    </div>

                    <!-- Step 7: Upgrade Plan -->
                    <div x-show="currentStep === 7" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="max-w-3xl mx-auto">
                            <div class="text-center mb-8">
                                <div
                                    class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Current Plan: {{ $currentPlan->name ?? 'Free' }}
                                </div>
                                <p class="text-gray-600">Unlock more features by upgrading your plan.</p>
                            </div>

                            <div class="grid md:grid-cols-3 gap-4">
                                @foreach($plans->take(3) as $plan)
                                    <div
                                        class="border-2 rounded-xl p-6 transition {{ $currentPlan && $currentPlan->id === $plan->id ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-sky-300' }}">
                                        @if($plan->is_popular)
                                            <div class="text-center mb-2">
                                                <span
                                                    class="px-3 py-1 bg-sky-500 text-white text-xs font-medium rounded-full">Popular</span>
                                            </div>
                                        @endif
                                        <h3 class="text-lg font-bold text-gray-900 text-center">{{ $plan->name }}</h3>
                                        <div class="text-center my-4">
                                            <span
                                                class="text-3xl font-bold text-gray-900">${{ number_format($plan->price, 0) }}</span>
                                            <span class="text-gray-500">/{{ $plan->billing_cycle }}</span>
                                        </div>
                                        <ul class="space-y-2 text-sm text-gray-600 mb-6">
                                            @if($plan->max_invoices)
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    {{ $plan->max_invoices }} Invoices
                                                </li>
                                            @else
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    Unlimited Invoices
                                                </li>
                                            @endif
                                            @if($plan->max_clients)
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    {{ $plan->max_clients }} Clients
                                                </li>
                                            @else
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    Unlimited Clients
                                                </li>
                                            @endif
                                            @if($plan->stripe_enabled)
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    Online Payments
                                                </li>
                                            @endif
                                            @if($plan->custom_branding)
                                                <li class="flex items-center">
                                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                                    Custom Branding
                                                </li>
                                            @endif
                                        </ul>
                                        @if($currentPlan && $currentPlan->id === $plan->id)
                                            <button disabled
                                                class="w-full py-2 px-4 bg-green-100 text-green-700 font-medium rounded-lg">
                                                <i class="fas fa-check mr-2"></i>Current Plan
                                            </button>
                                        @elseif($plan->price > 0)
                                            <a href="{{ route('settings.subscription.payment', $plan) }}"
                                                class="block w-full py-2 px-4 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg text-center transition">
                                                Upgrade Now
                                            </a>
                                        @else
                                            <button disabled
                                                class="w-full py-2 px-4 bg-gray-100 text-gray-500 font-medium rounded-lg">
                                                Free
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <p class="text-center text-gray-500 text-sm mt-6">
                                You can upgrade or change plans anytime from Settings → Subscription
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <button @click="previousStep" :disabled="currentStep === 1"
                        :class="currentStep === 1 ? 'invisible' : ''"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Previous
                    </button>

                    <div class="flex items-center space-x-3">
                        <button @click="skipStep"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium transition">
                            Skip
                        </button>

                        <template x-if="currentStep < steps.length">
                            <button @click="nextStep" :disabled="saving"
                                class="px-6 py-2 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition flex items-center">
                                <span x-show="!saving">Next <i class="fas fa-arrow-right ml-2"></i></span>
                                <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
                            </button>
                        </template>

                        <template x-if="currentStep === steps.length">
                            <form action="{{ route('onboarding.complete') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Complete Setup
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Skip All Link -->
            <div class="text-center mt-6">
                <form action="{{ route('onboarding.skip') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-white/70 hover:text-white text-sm transition">
                        Skip setup and go to dashboard →
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function setupWizard() {
                return {
                    currentStep: {{ $company->onboarding_step > 0 ? $company->onboarding_step : 1 }},
                    completedSteps: {!! json_encode(range(1, max(0, $company->onboarding_step - 1))) !!},
                    saving: false,
                    steps: [
                        { title: 'Company Logo', description: 'Upload your company logo for professional branding' },
                        { title: 'Business Address', description: 'Add your business address and contact information' },
                        { title: 'Payment Methods', description: 'Configure how clients can pay your invoices' },
                        { title: 'Tax Settings', description: 'Set up tax rates for your invoices' },
                        { title: 'Theme & Templates', description: 'Customize your brand colors and invoice design' },
                        { title: 'Team Members', description: 'Invite team members to collaborate' },
                        { title: 'Choose Your Plan', description: 'Select a plan that fits your needs' }
                    ],
                    formData: {
                        // Step 2: Address
                        address: '{{ $company->address }}',
                        city: '{{ $company->city }}',
                        state: '{{ $company->state }}',
                        postal_code: '{{ $company->postal_code }}',
                        country: '{{ $company->country }}',
                        phone: '{{ $company->phone }}',
                        // Step 3: Payment
                        accept_stripe: {{ $settings->accept_stripe ? 'true' : 'false' }},
                        payment_gateway_mode: '{{ $settings->payment_gateway_mode ?? 'app' }}',
                        stripe_key: '{{ $settings->stripe_key ?? '' }}',
                        stripe_secret: '',
                        accept_bank_transfer: {{ $settings->accept_bank_transfer ? 'true' : 'false' }},
                        accept_cash: {{ $settings->accept_cash ? 'true' : 'false' }},
                        bank_name: '{{ $settings->bank_name ?? '' }}',
                        bank_account_name: '{{ $settings->bank_account_name ?? '' }}',
                        bank_account_number: '{{ $settings->bank_account_number ?? '' }}',
                        // Step 4: Tax
                        tax_enabled: {{ $settings->tax_enabled ? 'true' : 'false' }},
                        default_tax_rate: {{ $settings->default_tax_rate ?? 0 }},
                        tax_number: '{{ $company->tax_number ?? '' }}',
                        // Step 5: Theme
                        primary_color: '{{ $settings->primary_color ?? '#0ea5e9' }}',
                        invoice_template: '{{ $settings->invoice_template ?? 'modern' }}',
                        // Step 6: Team
                        team_name: '',
                        team_email: '',
                        team_role: 'member'
                    },

                    goToStep(step) {
                        if (step <= this.currentStep || this.completedSteps.includes(step - 1)) {
                            this.currentStep = step;
                        }
                    },

                    async nextStep() {
                        await this.saveCurrentStep();
                        if (this.currentStep < this.steps.length) {
                            if (!this.completedSteps.includes(this.currentStep)) {
                                this.completedSteps.push(this.currentStep);
                            }
                            this.currentStep++;
                        }
                    },

                    previousStep() {
                        if (this.currentStep > 1) {
                            this.currentStep--;
                        }
                    },

                    skipStep() {
                        if (this.currentStep < this.steps.length) {
                            this.currentStep++;
                        }
                    },

                    async saveCurrentStep() {
                        this.saving = true;
                        const formData = new FormData();

                        // Add data based on current step
                        switch (this.currentStep) {
                            case 2: // Address
                                formData.append('address', this.formData.address);
                                formData.append('city', this.formData.city);
                                formData.append('state', this.formData.state);
                                formData.append('postal_code', this.formData.postal_code);
                                formData.append('country', this.formData.country);
                                formData.append('phone', this.formData.phone);
                                break;
                            case 3: // Payment
                                formData.append('accept_stripe', this.formData.accept_stripe ? '1' : '0');
                                formData.append('payment_gateway_mode', this.formData.payment_gateway_mode);
                                if (this.formData.payment_gateway_mode === 'own') {
                                    formData.append('stripe_key', this.formData.stripe_key);
                                    formData.append('stripe_secret', this.formData.stripe_secret);
                                }
                                formData.append('accept_bank_transfer', this.formData.accept_bank_transfer ? '1' : '0');
                                formData.append('accept_cash', this.formData.accept_cash ? '1' : '0');
                                formData.append('bank_name', this.formData.bank_name);
                                formData.append('bank_account_name', this.formData.bank_account_name);
                                formData.append('bank_account_number', this.formData.bank_account_number);
                                break;
                            case 4: // Tax
                                formData.append('tax_enabled', this.formData.tax_enabled ? '1' : '0');
                                formData.append('default_tax_rate', this.formData.default_tax_rate);
                                formData.append('tax_number', this.formData.tax_number);
                                break;
                            case 5: // Theme
                                formData.append('primary_color', this.formData.primary_color);
                                formData.append('invoice_template', this.formData.invoice_template);
                                break;
                            case 6: // Team
                                if (this.formData.team_email) {
                                    formData.append('team_name', this.formData.team_name);
                                    formData.append('team_email', this.formData.team_email);
                                    formData.append('team_role', this.formData.team_role);
                                }
                                break;
                        }

                        try {
                            const response = await fetch(`/onboarding/step/${this.currentStep}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const result = await response.json();
                            if (!result.success) {
                                console.error('Failed to save step:', result);
                            }
                        } catch (error) {
                            console.error('Error saving step:', error);
                        } finally {
                            this.saving = false;
                        }
                    },

                    handleLogoUpload(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        const formData = new FormData();
                        formData.append('logo', file);

                        this.saving = true;
                        fetch('/onboarding/step/1', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    // Update preview
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        const container = this.$refs.logoInput.closest('.max-w-md').querySelector('.w-32.h-32');
                                        container.innerHTML = `<img src="${e.target.result}" alt="Logo" class="w-full h-full object-contain">`;
                                    };
                                    reader.readAsDataURL(file);

                                    if (!this.completedSteps.includes(1)) {
                                        this.completedSteps.push(1);
                                    }
                                }
                            })
                            .finally(() => {
                                this.saving = false;
                            });
                    }
                }
            }
        </script>
    @endpush
@endsection