@extends('layouts.app')

@section('title', 'Settings - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your company settings and preferences')

@section('content')
    <div x-data="{ activeTab: 'company' }" class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="border-b border-gray-100">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'company'"
                    :class="activeTab === 'company' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Company
                </button>
                <button @click="activeTab = 'invoice'"
                    :class="activeTab === 'invoice' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Invoice
                </button>
                <button @click="activeTab = 'quotation'"
                    :class="activeTab === 'quotation' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Quotation
                </button>
                <button @click="activeTab = 'preferences'"
                    :class="activeTab === 'preferences' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Preferences
                </button>
                <button @click="activeTab = 'payment'"
                    :class="activeTab === 'payment' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Payment
                </button>
                <button @click="activeTab = 'theme'"
                    :class="activeTab === 'theme' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Theme
                </button>
                <button @click="activeTab = 'notes_terms'"
                    :class="activeTab === 'notes_terms' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                    Notes & Terms
                </button>
                <a href="{{ route('settings.subscription') }}"
                    class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors">
                    Plan & Billing
                </a>
            </nav>
        </div>

        <div x-show="activeTab === 'notes_terms'" x-cloak class="p-6">
            <form action="{{ route('settings.notes-terms') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="default_notes" class="block text-sm font-medium text-gray-700 mb-1">Default Notes</label>
                    <textarea name="default_notes" id="default_notes" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('default_notes', $settings->default_notes) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">These notes will be pre-filled on new invoices and quotations.</p>
                </div>

                <div>
                    <label for="default_terms" class="block text-sm font-medium text-gray-700 mb-1">Default Terms & Conditions</label>
                    <textarea name="default_terms" id="default_terms" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('default_terms', $settings->default_terms) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">These terms will be pre-filled on new invoices and quotations.</p>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'company'" class="p-6">
            <form action="{{ route('settings.company') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="flex items-center space-x-6 mb-6">
                    @if($company->logo)
                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }}"
                            class="w-24 h-24 rounded-lg object-cover">
                    @else
                        <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-gray-400 text-3xl"></i>
                        </div>
                    @endif
                    <div>
                        <label for="logo"
                            class="cursor-pointer text-sm text-primary text-primary-hover font-medium">
                            Upload Logo
                        </label>
                        <input type="file" name="logo" id="logo" class="hidden" accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 2MB</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $company->email) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $company->phone) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                        <input type="url" name="website" id="website" value="{{ old('website', $company->website) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">Tax Number / VAT
                            ID</label>
                        <input type="text" name="tax_number" id="tax_number"
                            value="{{ old('tax_number', $company->tax_number) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                        <select name="currency" id="currency" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @foreach($currencies as $code => $label)
                                <option value="{{ $code }}" {{ old('currency', $company->currency) === $code ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" id="address" rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('address', $company->address) }}</textarea>
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" name="city" id="city" value="{{ old('city', $company->city) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                        <input type="text" name="state" id="state" value="{{ old('state', $company->state) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code"
                            value="{{ old('postal_code', $company->postal_code) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <input type="text" name="country" id="country" value="{{ old('country', $company->country) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'invoice'" x-cloak class="p-6">
            <form action="{{ route('settings.invoice') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="invoice_prefix" class="block text-sm font-medium text-gray-700 mb-1">Invoice
                            Prefix</label>
                        <input type="text" name="invoice_prefix" id="invoice_prefix"
                            value="{{ old('invoice_prefix', $company->invoice_prefix) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">e.g., INV-, INVOICE-</p>
                    </div>

                    <div>
                        <label for="invoice_start_number" class="block text-sm font-medium text-gray-700 mb-1">Starting
                            Number</label>
                        <input type="number" name="invoice_start_number" id="invoice_start_number"
                            value="{{ old('invoice_start_number', $company->invoice_start_number) }}" min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">Default Payment
                            Terms (Days)</label>
                        <input type="number" name="payment_terms" id="payment_terms"
                            value="{{ old('payment_terms', $company->payment_terms) }}" min="0" max="365"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="md:col-span-2">
                        <label for="invoice_footer" class="block text-sm font-medium text-gray-700 mb-1">Invoice
                            Footer</label>
                        <textarea name="invoice_footer" id="invoice_footer" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('invoice_footer', $company->invoice_footer) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">This will appear at the bottom of all invoices</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="authorized_signature" class="block text-sm font-medium text-gray-700 mb-1">Authorized
                            Signature Image</label>
                        @if($company->signature_url)
                            <img src="{{ $company->signature_url }}" alt="Signature" class="h-12 mb-2 object-contain">
                        @endif
                        <input type="file" name="authorized_signature" id="authorized_signature" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Upload a signature image to print above the "Authorized Signature" line on invoices.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_invoice_footer" value="1" {{ old('show_invoice_footer', $settings->show_invoice_footer) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Footer on Invoices</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="show_authorized_signature" value="1" {{ old('show_authorized_signature', $settings->show_authorized_signature) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Authorized Signature on Invoices</span>
                    </label>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'quotation'" x-cloak class="p-6">
            <form action="{{ route('settings.quotation') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="quotation_prefix" class="block text-sm font-medium text-gray-700 mb-1">Quotation
                        Prefix</label>
                    <input type="text" name="quotation_prefix" id="quotation_prefix"
                        value="{{ old('quotation_prefix', $company->quotation_prefix) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">e.g., QT-, QUOTE-</p>
                </div>

                <div>
                    <label for="quotation_validity_days" class="block text-sm font-medium text-gray-700 mb-1">Default
                        Validity Period (Days)</label>
                    <input type="number" name="quotation_validity_days" id="quotation_validity_days"
                        value="{{ old('quotation_validity_days', $company->quotation_validity_days) }}" min="0" max="365"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Number of days a new quotation stays valid from its issue date.</p>
                </div>

                <div>
                    <label for="quotation_message" class="block text-sm font-medium text-gray-700 mb-1">Quotation
                        Message</label>
                    <textarea name="quotation_message" id="quotation_message" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('quotation_message', $settings->quotation_message) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">A custom message shown near the bottom of every quotation.</p>
                </div>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_quotation_message" value="1" {{ old('show_quotation_message', $settings->show_quotation_message) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Message on Quotations</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="show_quotation_website" value="1" {{ old('show_quotation_website', $settings->show_quotation_website) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Company Website on Quotations</span>
                    </label>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'preferences'" x-cloak class="p-6">
            <form action="{{ route('settings.preferences') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                        <select name="date_format" id="date_format"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @foreach($dateFormats as $format => $label)
                                <option value="{{ $format }}" {{ old('date_format', $settings->date_format) === $format ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                        <select name="timezone" id="timezone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', $settings->timezone) === $tz ? 'selected' : '' }}>
                                    {{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="default_tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Default Tax Rate
                            (%)</label>
                        <input type="number" name="default_tax_rate" id="default_tax_rate"
                            value="{{ old('default_tax_rate', $settings->default_tax_rate) }}" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                </div>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="tax_enabled" value="1" {{ old('tax_enabled', $settings->tax_enabled) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Enable Tax Calculations</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" value="1" {{ old('email_notifications', $settings->email_notifications) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Email Notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="invoice_due_reminder" value="1" {{ old('invoice_due_reminder', $settings->invoice_due_reminder) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Invoice Due Reminders</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="show_logo_on_invoice" value="1" {{ old('show_logo_on_invoice', $settings->show_logo_on_invoice) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Logo on Invoices</span>
                    </label>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'payment'" x-cloak class="p-6">
            <form action="{{ route('settings.payment') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_payment_instructions" value="1" {{ old('show_payment_instructions', $settings->show_payment_instructions) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Show Payment Instructions on Invoices</span>
                    </label>
                </div>

                <div>
                    <label for="payment_instructions" class="block text-sm font-medium text-gray-700 mb-1">Payment
                        Instructions</label>
                    <textarea name="payment_instructions" id="payment_instructions" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('payment_instructions', $settings->payment_instructions) }}</textarea>
                </div>

                <h3 class="text-lg font-medium text-gray-900 pt-4">Bank Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name"
                            value="{{ old('bank_name', $settings->bank_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">Account
                            Name</label>
                        <input type="text" name="bank_account_name" id="bank_account_name"
                            value="{{ old('bank_account_name', $settings->bank_account_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Account
                            Number</label>
                        <input type="text" name="bank_account_number" id="bank_account_number"
                            value="{{ old('bank_account_number', $settings->bank_account_number) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="bank_routing_number" class="block text-sm font-medium text-gray-700 mb-1">Routing Number
                            / SWIFT</label>
                        <input type="text" name="bank_routing_number" id="bank_routing_number"
                            value="{{ old('bank_routing_number', $settings->bank_routing_number) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-900 pt-4">PayPal</h3>
                <div>
                    <label for="paypal_email" class="block text-sm font-medium text-gray-700 mb-1">PayPal Email</label>
                    <input type="email" name="paypal_email" id="paypal_email"
                        value="{{ old('paypal_email', $settings->paypal_email) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Accepted Payment Methods</h3>
                    <p class="text-sm text-gray-600 mb-4">Select which payment methods clients can use when paying for
                        quotations and invoices.</p>

                    <div class="space-y-3 mb-6">
                        <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                            <input type="checkbox" name="accept_bank_transfer" value="1" {{ old('accept_bank_transfer', $settings->accept_bank_transfer ?? true) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <i class="fas fa-university text-gray-500 ml-3 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">Bank Transfer</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="fab fa-stripe text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <h4 class="font-medium text-blue-900">Stripe Payment Integration</h4>
                                <p class="text-sm text-blue-700 mt-1">Accept online payments directly from your invoices.
                                    Clients can pay with credit/debit cards.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                            <input type="checkbox" name="stripe_enabled" value="1" {{ old('stripe_enabled', $settings->stripe_enabled) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <input type="hidden" name="accept_stripe" value="{{ old('stripe_enabled', $settings->stripe_enabled) ? '1' : '0' }}">
                            <i class="fab fa-stripe text-gray-500 ml-3 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">Enable Stripe Card Payments</span>
                        </label>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Gateway Mode</label>
                        <div class="space-y-3">
                            <label
                                class="flex items-start p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 border-2 {{ ($settings->payment_gateway_mode ?? 'app') === 'app' ? 'border-primary' : 'border-transparent' }}">
                                <input type="radio" name="payment_gateway_mode" value="app" {{ old('payment_gateway_mode', $settings->payment_gateway_mode ?? 'app') === 'app' ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary border-gray-300 focus:ring-primary mt-0.5">
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-900">Use App's Payment Gateway</span>
                                    <p class="text-xs text-gray-500 mt-1">Use the platform's Stripe account for payment
                                        processing. Simpler setup.</p>
                                </div>
                            </label>

                            <label
                                class="flex items-start p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 border-2 {{ ($settings->payment_gateway_mode ?? 'app') === 'own' ? 'border-primary' : 'border-transparent' }}">
                                <input type="radio" name="payment_gateway_mode" value="own" {{ old('payment_gateway_mode', $settings->payment_gateway_mode ?? 'app') === 'own' ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary border-gray-300 focus:ring-primary mt-0.5">
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-900">Use Your Own Stripe Account</span>
                                    <p class="text-xs text-gray-500 mt-1">Connect your own Stripe account to receive
                                        payments directly. Requires Stripe API keys below.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="stripe_public_key" class="block text-sm font-medium text-gray-700 mb-1">Publishable
                                Key</label>
                            <input type="text" name="stripe_public_key" id="stripe_public_key"
                                value="{{ old('stripe_public_key', $settings->stripe_public_key) }}"
                                placeholder="pk_live_..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>

                        <div>
                            <label for="stripe_secret_key" class="block text-sm font-medium text-gray-700 mb-1">Secret
                                Key</label>
                            <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                                value="{{ old('stripe_secret_key', $settings->stripe_secret_key) }}"
                                placeholder="sk_live_..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>

                        <div class="md:col-span-2">
                            <label for="stripe_webhook_secret" class="block text-sm font-medium text-gray-700 mb-1">Webhook
                                Secret (Optional)</label>
                            <input type="password" name="stripe_webhook_secret" id="stripe_webhook_secret"
                                value="{{ old('stripe_webhook_secret', $settings->stripe_webhook_secret) }}"
                                placeholder="whsec_..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-500 mt-1">Webhook URL: <code
                                    class="bg-gray-100 px-2 py-1 rounded">{{ route('stripe.webhook') }}</code></p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div x-show="activeTab === 'theme'" x-cloak class="p-6">
            <form action="{{ route('settings.theme') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Color Presets</h3>
                    <div class="grid grid-cols-4 md:grid-cols-8 gap-3">
                        @foreach($colorPresets as $name => $colors)
                            <button type="button"
                                onclick="applyPreset('{{ $colors['primary'] }}', '{{ $colors['secondary'] }}', '{{ $colors['sidebar'] }}', '{{ $colors['sidebar_text'] }}')"
                                class="w-full aspect-square rounded-lg border-2 border-gray-200 hover:border-gray-400 transition overflow-hidden"
                                title="{{ ucfirst($name) }}">
                                <div class="h-1/2" style="background-color: {{ $colors['primary'] }}"></div>
                                <div class="h-1/2" style="background-color: {{ $colors['sidebar'] }}"></div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Primary
                            Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="primary_color" id="primary_color"
                                value="{{ old('primary_color', $settings->primary_color ?? '#4f46e5') }}"
                                class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text" id="primary_color_text"
                                value="{{ old('primary_color', $settings->primary_color ?? '#4f46e5') }}"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                    </div>

                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-1">Secondary
                            Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="secondary_color" id="secondary_color"
                                value="{{ old('secondary_color', $settings->secondary_color ?? '#6366f1') }}"
                                class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text" id="secondary_color_text"
                                value="{{ old('secondary_color', $settings->secondary_color ?? '#6366f1') }}"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                    </div>

                    <div>
                        <label for="sidebar_color" class="block text-sm font-medium text-gray-700 mb-1">Sidebar
                            Background</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="sidebar_color" id="sidebar_color"
                                value="{{ old('sidebar_color', $settings->sidebar_color ?? '#1e1b4b') }}"
                                class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text" id="sidebar_color_text"
                                value="{{ old('sidebar_color', $settings->sidebar_color ?? '#1e1b4b') }}"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                    </div>

                    <div>
                        <label for="sidebar_text_color" class="block text-sm font-medium text-gray-700 mb-1">Sidebar Text
                            Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="sidebar_text_color" id="sidebar_text_color"
                                value="{{ old('sidebar_text_color', $settings->sidebar_text_color ?? '#ffffff') }}"
                                class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text" id="sidebar_text_color_text"
                                value="{{ old('sidebar_text_color', $settings->sidebar_text_color ?? '#ffffff') }}"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Typography</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="font_family" class="block text-sm font-medium text-gray-700 mb-1">Font
                                Family</label>
                            <select name="font_family" id="font_family"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                @foreach($fontFamilies as $value => $label)
                                    <option value="{{ $value }}" {{ old('font_family', $settings->font_family ?? 'Inter') === $value ? 'selected' : '' }} style="font-family: '{{ $value }}'">{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="font_size" class="block text-sm font-medium text-gray-700 mb-1">Font Size</label>
                            <select name="font_size" id="font_size"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                @foreach($fontSizes as $value => $label)
                                    <option value="{{ $value }}" {{ old('font_size', $settings->font_size ?? 'base') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="heading_color" class="block text-sm font-medium text-gray-700 mb-1">Heading
                                Color</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="heading_color" id="heading_color"
                                    value="{{ old('heading_color', $settings->heading_color ?? '#111827') }}"
                                    class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                                <input type="text" id="heading_color_text"
                                    value="{{ old('heading_color', $settings->heading_color ?? '#111827') }}"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                            </div>
                        </div>

                        <div>
                            <label for="text_color" class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="text_color" id="text_color"
                                    value="{{ old('text_color', $settings->text_color ?? '#374151') }}"
                                    class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                                <input type="text" id="text_color_text"
                                    value="{{ old('text_color', $settings->text_color ?? '#374151') }}"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div x-data="{ showPreview: false, previewUrl: '', previewName: '' }">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Template</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            @foreach($invoiceTemplates as $key => $template)
                                <label class="cursor-pointer group relative" x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false">
                                    <input type="radio" name="invoice_template" value="{{ $key }}" {{ old('invoice_template', $settings->invoice_template ?? 'classic') === $key ? 'checked' : '' }} class="sr-only peer">
                                    
                                    <div class="p-1 border-2 rounded-xl peer-checked:border-primary peer-checked:bg-primary-light hover:border-gray-300 transition-all duration-200 relative bg-white shadow-sm h-full flex flex-col">
                                        <!-- Thumbnail Container -->
                                        <div class="w-full aspect-[21/29.7] bg-gray-100 rounded-lg mb-3 overflow-hidden border border-gray-200 relative group-hover:shadow-md transition-shadow">
                                            <!-- Scaled Iframe -->
                                            <x-template-thumbnail :src="route('settings.template.preview', $key)" />
                                            
                                            <!-- Selection Overlay -->
                                            <div class="absolute inset-0 bg-primary/10 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></div>

                                            <!-- Preview Button Overlay -->
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[1px]">
                                                <button type="button" 
                                                    @click.prevent="showPreview = true; previewUrl = '{{ route('settings.template.preview', $key) }}'; previewName = '{{ $template['name'] }}'"
                                                    class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-medium shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-all hover:bg-gray-50">
                                                    <i class="fas fa-eye mr-2"></i> Preview
                                                </button>
                                            </div>

                                            <!-- Selected Indicator -->
                                            <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity transform scale-50 peer-checked:scale-100">
                                                <div class="bg-primary-light0 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-sm">
                                                    <i class="fas fa-check text-xs"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="px-2 pb-2">
                                            <p class="font-semibold text-gray-900 text-sm mb-0.5">{{ $template['name'] }}</p>
                                            <p class="text-xs text-gray-500 line-clamp-2">{{ $template['description'] }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <!-- Full Screen Preview Modal -->
                        <div x-show="showPreview" 
                            style="display: none;"
                            class="fixed inset-0 z-50 overflow-y-auto" 
                            aria-labelledby="modal-title" 
                            role="dialog" 
                            aria-modal="true">
                            
                            <!-- Backdrop -->
                            <div x-show="showPreview"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" 
                                @click="showPreview = false"></div>

                            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="showPreview"
                                    x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-5xl h-[90vh] flex flex-col">
                                    
                                    <!-- Modal Header -->
                                    <div class="bg-white px-4 py-3 border-b border-gray-200 flex items-center justify-between sticky top-0 z-10">
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                                <span x-text="previewName"></span> Template Preview
                                            </h3>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md">Sample Data</span>
                                        </div>
                                        <button type="button" @click="showPreview = false" class="text-gray-400 hover:text-gray-500 focus:outline-none p-2 rounded-full hover:bg-gray-100 transition-colors">
                                            <span class="sr-only">Close</span>
                                            <i class="fas fa-times text-lg"></i>
                                        </button>
                                    </div>

                                    <!-- Modal Body (Iframe) -->
                                    <div class="flex-1 bg-gray-50 relative w-full h-full overflow-hidden">
                                        <iframe :src="previewUrl" class="w-full h-full border-none" style="display: block;"></iframe>
                                    </div>
                                    
                                    <!-- Modal Footer -->
                                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-200">
                                        <button type="button" 
                                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" 
                                            @click="showPreview = false">
                                            Close Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 pb-2 border-t mt-6">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    @push('scripts')
        <script>
            function applyPreset(primary, secondary, sidebar, sidebarText) {
                document.getElementById('primary_color').value = primary;
                document.getElementById('primary_color_text').value = primary;
                document.getElementById('secondary_color').value = secondary;
                document.getElementById('secondary_color_text').value = secondary;
                document.getElementById('sidebar_color').value = sidebar;
                document.getElementById('sidebar_color_text').value = sidebar;
                document.getElementById('sidebar_text_color').value = sidebarText;
                document.getElementById('sidebar_text_color_text').value = sidebarText;
            }

            document.querySelectorAll('input[type="color"]').forEach(input => {
                input.addEventListener('input', function () {
                    document.getElementById(this.id + '_text').value = this.value;
                });
            });
        </script>
    @endpush
@endsection