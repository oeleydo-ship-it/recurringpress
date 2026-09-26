@extends('superadmin.layout')

@section('title', 'General Settings')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <p class="text-gray-600">Configure your site branding and general settings.</p>
        </div>

        <form action="{{ route('superadmin.settings.general.update') }}" method="POST" enctype="multipart/form-data"
            class="bg-white rounded-xl shadow-sm p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Name *</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Support Email</label>
                        <input type="email" name="support_email"
                            value="{{ old('support_email', $settings['support_email']) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Site Tagline</label>
                    <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
                        @if($settings['site_logo'])
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $settings['site_logo']) }}" class="h-12 object-contain"
                                    alt="Logo">
                            </div>
                        @endif
                        <input type="file" name="site_logo" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 200x50px, PNG or SVG</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                        @if($settings['site_favicon'])
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $settings['site_favicon']) }}" class="h-8 w-8 object-contain"
                                    alt="Favicon">
                            </div>
                        @endif
                        <input type="file" name="site_favicon" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 32x32px, PNG or ICO</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                    <input type="checkbox" name="hide_site_name" id="hide_site_name" value="1" {{ old('hide_site_name', $settings['hide_site_name']) ? 'checked' : '' }}
                        class="w-5 h-5 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                    <div>
                        <label for="hide_site_name" class="font-medium text-gray-700 cursor-pointer">Hide Site Name</label>
                        <p class="text-sm text-gray-500">Only show the logo without the site name on the landing page and
                            login/register pages</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                    <input type="checkbox" name="email_verification_enabled" id="email_verification_enabled" value="1" {{ old('email_verification_enabled', $settings['email_verification_enabled']) ? 'checked' : '' }}
                        class="w-5 h-5 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                    <div>
                        <label for="email_verification_enabled" class="font-medium text-gray-700 cursor-pointer">Require
                            Email Verification</label>
                        <p class="text-sm text-gray-500">New users must verify their email address before accessing the
                            dashboard. Requires SMTP to be configured.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Footer Text</label>
                    <textarea name="footer_text" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('footer_text', $settings['footer_text']) }}</textarea>
                </div>

                {{-- Tracking & Live Chat Settings --}}
                <div class="pt-6 mt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-chart-line mr-2 text-sky-500"></i>Tracking & Live Chat
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Google Analytics Script</label>
                            <textarea name="google_analytics_script" rows="5"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Paste your Google Analytics script here.">{{ old('google_analytics_script', $settings['google_analytics_script'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Paste the full Google Analytics embed script. Leave blank to disable analytics.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Live Chat Script</label>
                            <textarea name="livechat_script" rows="5"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Paste your live chat embed script here (e.g., Tawk.to, Crisp, Intercom).">{{ old('livechat_script', $settings['livechat_script'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">This script will be injected on public and app pages.</p>
                        </div>
                    </div>
                </div>

                {{-- SEO Settings Section --}}
                <div class="pt-6 mt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-search mr-2 text-sky-500"></i>SEO Settings
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                            <textarea name="seo_description" rows="2" maxlength="160"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Professional invoicing software for freelancers and small businesses.">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Default meta description for the site (max 160
                                characters). Individual pages can override this.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                            <input type="text" name="seo_keywords"
                                value="{{ old('seo_keywords', $settings['seo_keywords'] ?? '') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="invoicing, billing, invoice software, freelancer invoicing">
                            <p class="text-xs text-gray-500 mt-1">Comma-separated keywords for SEO.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Social Share Image (OG
                                Image)</label>
                            @if($settings['seo_og_image'] ?? null)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['seo_og_image']) }}"
                                        class="h-24 object-contain rounded border" alt="OG Image">
                                </div>
                            @endif
                            <input type="file" name="seo_og_image" accept="image/*"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <p class="text-xs text-gray-500 mt-1">Recommended: 1200x630px. Used when sharing your site on
                                social media.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-8 pt-6 border-t">
                <button type="submit"
                    class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
@endsection