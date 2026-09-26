@extends('superadmin.layout')

@section('title', 'Landing Page Settings')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6">
            <p class="text-gray-600">Customize all sections of your landing page.</p>
        </div>

        <form action="{{ route('superadmin.settings.landing.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- Hero Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-home text-sky-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Hero Section</h3>
                        <p class="text-sm text-gray-500">The main banner area of your homepage</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hero Title *</label>
                        <input type="text" name="landing_hero_title"
                            value="{{ old('landing_hero_title', $settings['landing_hero_title']) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hero Subtitle *</label>
                        <textarea name="landing_hero_subtitle" rows="3" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('landing_hero_subtitle', $settings['landing_hero_subtitle']) }}</textarea>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button Text *</label>
                            <input type="text" name="landing_cta_text"
                                value="{{ old('landing_cta_text', $settings['landing_cta_text']) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button URL *</label>
                            <input type="text" name="landing_cta_url"
                                value="{{ old('landing_cta_url', $settings['landing_cta_url']) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trust Badges Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-building text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Trust Badges</h3>
                        <p class="text-sm text-gray-500">Company logos/names shown below hero</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trust Text</label>
                        <input type="text" name="landing_trust_text"
                            value="{{ old('landing_trust_text', $settings['landing_trust_text']) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                            placeholder="Trusted by thousands of freelancers and businesses">
                    </div>
                    <div x-data="{
                                    badges: {{ $settings['landing_trust_badges_json'] }},
                                    addBadge() {
                                        this.badges.push({ name: '' });
                                    },
                                    removeBadge(index) {
                                        this.badges.splice(index, 1);
                                    }
                                }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Names/Badges</label>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <template x-for="(badge, index) in badges" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                    <input type="text" x-model="badge.name" placeholder="Company Name"
                                        class="px-3 py-1 border border-gray-300 rounded-lg text-sm w-32">
                                    <button type="button" @click="removeBadge(index)"
                                        class="text-red-500 hover:bg-red-50 rounded p-1">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addBadge()"
                            class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-lg hover:bg-sky-50">
                            <i class="fas fa-plus mr-2"></i>Add Badge
                        </button>
                        <input type="hidden" name="landing_trust_badges_json" :value="JSON.stringify(badges)">
                    </div>
                </div>
            </div>

            {{-- Features Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-th-large text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Features Section</h3>
                        <p class="text-sm text-gray-500">Showcase what your product offers</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Title *</label>
                            <input type="text" name="landing_features_title"
                                value="{{ old('landing_features_title', $settings['landing_features_title']) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Subtitle *</label>
                            <input type="text" name="landing_features_subtitle"
                                value="{{ old('landing_features_subtitle', $settings['landing_features_subtitle']) }}"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                    </div>
                    <div x-data="{
                                    features: {{ $settings['landing_features_json'] }},
                                    addFeature() {
                                        this.features.push({ icon: 'fas fa-star', color: 'sky', title: '', description: '' });
                                    },
                                    removeFeature(index) {
                                        this.features.splice(index, 1);
                                    }
                                }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                        <div class="space-y-3">
                            <template x-for="(feature, index) in features" :key="index">
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="grid md:grid-cols-4 gap-3 mb-3">
                                        <div>
                                            <label class="text-xs text-gray-500">Icon Class</label>
                                            <input type="text" x-model="feature.icon" placeholder="fas fa-star"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Color</label>
                                            <select x-model="feature.color"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option value="sky">Sky</option>
                                                <option value="green">Green</option>
                                                <option value="purple">Purple</option>
                                                <option value="blue">Blue</option>
                                                <option value="orange">Orange</option>
                                                <option value="pink">Pink</option>
                                                <option value="red">Red</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs text-gray-500">Title</label>
                                            <input type="text" x-model="feature.title" placeholder="Feature Title"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500">Description</label>
                                            <input type="text" x-model="feature.description"
                                                placeholder="Feature description..."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <button type="button" @click="removeFeature(index)"
                                            class="self-end px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addFeature()"
                            class="mt-3 px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-lg hover:bg-sky-50">
                            <i class="fas fa-plus mr-2"></i>Add Feature
                        </button>
                        <input type="hidden" name="landing_features_json" :value="JSON.stringify(features)">
                    </div>
                </div>
            </div>

            {{-- Features Page (Dedicated /features page) --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-list-alt text-indigo-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Features Page</h3>
                        <p class="text-sm text-gray-500">Dedicated /features page content with detailed feature cards</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page Title</label>
                            <input type="text" name="features_page_title"
                                value="{{ old('features_page_title', $settings['features_page_title']) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Powerful Features for Your Business">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page Subtitle</label>
                            <input type="text" name="features_page_subtitle"
                                value="{{ old('features_page_subtitle', $settings['features_page_subtitle']) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Everything you need to create professional invoices...">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CTA Title</label>
                            <input type="text" name="features_page_cta_title"
                                value="{{ old('features_page_cta_title', $settings['features_page_cta_title']) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Ready to get started?">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CTA Subtitle</label>
                            <input type="text" name="features_page_cta_subtitle"
                                value="{{ old('features_page_cta_subtitle', $settings['features_page_cta_subtitle']) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                placeholder="Use {site_name} as a placeholder for site name">
                        </div>
                    </div>
                    <div x-data="{
                                    features: {{ $settings['features_page_json'] ?? '[]' }},
                                    addFeature() {
                                        this.features.push({ icon: 'fas fa-star', color: 'sky', title: '', description: '', bullets: ['', '', ''] });
                                    },
                                    removeFeature(index) {
                                        this.features.splice(index, 1);
                                    },
                                    addBullet(feature) {
                                        if (!feature.bullets) feature.bullets = [];
                                        feature.bullets.push('');
                                    },
                                    removeBullet(feature, bulletIndex) {
                                        feature.bullets.splice(bulletIndex, 1);
                                    }
                                }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Feature Cards</label>
                        <div class="space-y-3">
                            <template x-for="(feature, index) in features" :key="index">
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="grid md:grid-cols-4 gap-3 mb-3">
                                        <div>
                                            <label class="text-xs text-gray-500">Icon Class</label>
                                            <input type="text" x-model="feature.icon" placeholder="fas fa-star"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Color</label>
                                            <select x-model="feature.color"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option value="sky">Sky</option>
                                                <option value="green">Green</option>
                                                <option value="purple">Purple</option>
                                                <option value="blue">Blue</option>
                                                <option value="orange">Orange</option>
                                                <option value="pink">Pink</option>
                                                <option value="red">Red</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs text-gray-500">Title</label>
                                            <input type="text" x-model="feature.title" placeholder="Feature Title"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-xs text-gray-500">Description</label>
                                        <input type="text" x-model="feature.description"
                                            placeholder="Feature description..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-xs text-gray-500">Bullet Points</label>
                                        <div class="space-y-2">
                                            <template x-for="(bullet, bulletIndex) in (feature.bullets || [])"
                                                :key="bulletIndex">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                                    <input type="text" x-model="feature.bullets[bulletIndex]"
                                                        placeholder="Bullet point"
                                                        class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                                                    <button type="button" @click="removeBullet(feature, bulletIndex)"
                                                        class="text-red-400 hover:text-red-600">
                                                        <i class="fas fa-times text-xs"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <button type="button" @click="addBullet(feature)"
                                                class="text-xs text-sky-600 hover:text-sky-700">
                                                <i class="fas fa-plus mr-1"></i>Add Bullet
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" @click="removeFeature(index)"
                                            class="px-3 py-1 text-red-500 hover:bg-red-50 rounded-lg text-sm">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addFeature()"
                            class="mt-3 px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-lg hover:bg-sky-50">
                            <i class="fas fa-plus mr-2"></i>Add Feature Card
                        </button>
                        <input type="hidden" name="features_page_json" :value="JSON.stringify(features)">
                    </div>
                </div>
            </div>

            {{-- Pricing Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-tag text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Pricing Section</h3>
                        <p class="text-sm text-gray-500">Header text for the pricing section (cards from Plans)</p>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section Title *</label>
                        <input type="text" name="landing_pricing_title"
                            value="{{ old('landing_pricing_title', $settings['landing_pricing_title']) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section Subtitle *</label>
                        <input type="text" name="landing_pricing_subtitle"
                            value="{{ old('landing_pricing_subtitle', $settings['landing_pricing_subtitle']) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>

            {{-- Testimonials Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-star text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Testimonials Section</h3>
                        <p class="text-sm text-gray-500">Customer reviews and testimonials</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Title *</label>
                            <input type="text" name="landing_testimonials_title"
                                value="{{ old('landing_testimonials_title', $settings['landing_testimonials_title']) }}"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Subtitle *</label>
                            <input type="text" name="landing_testimonials_subtitle"
                                value="{{ old('landing_testimonials_subtitle', $settings['landing_testimonials_subtitle']) }}"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                    </div>
                    <div x-data="{
                                    testimonials: {{ $settings['landing_testimonials_json'] }},
                                    addTestimonial() {
                                        this.testimonials.push({ name: '', role: '', initials: '', color: 'sky', quote: '' });
                                    },
                                    removeTestimonial(index) {
                                        this.testimonials.splice(index, 1);
                                    }
                                }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Testimonials</label>
                        <div class="space-y-3">
                            <template x-for="(testimonial, index) in testimonials" :key="index">
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="grid md:grid-cols-4 gap-3 mb-3">
                                        <div>
                                            <label class="text-xs text-gray-500">Name</label>
                                            <input type="text" x-model="testimonial.name" placeholder="John Doe"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Role</label>
                                            <input type="text" x-model="testimonial.role" placeholder="CEO, Company"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Initials</label>
                                            <input type="text" x-model="testimonial.initials" placeholder="JD" maxlength="2"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Color</label>
                                            <select x-model="testimonial.color"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option value="sky">Sky</option>
                                                <option value="green">Green</option>
                                                <option value="purple">Purple</option>
                                                <option value="blue">Blue</option>
                                                <option value="orange">Orange</option>
                                                <option value="pink">Pink</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500">Quote</label>
                                            <textarea x-model="testimonial.quote" rows="2" placeholder="What they said..."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                                        </div>
                                        <button type="button" @click="removeTestimonial(index)"
                                            class="self-end px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addTestimonial()"
                            class="mt-3 px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-lg hover:bg-sky-50">
                            <i class="fas fa-plus mr-2"></i>Add Testimonial
                        </button>
                        <input type="hidden" name="landing_testimonials_json" :value="JSON.stringify(testimonials)">
                    </div>
                </div>
            </div>

            {{-- Footer Section --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-window-minimize text-gray-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Footer Section</h3>
                        <p class="text-sm text-gray-500">Footer tagline text</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Footer Text</label>
                    <input type="text" name="landing_footer_text"
                        value="{{ old('landing_footer_text', $settings['landing_footer_text']) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit"
                    class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                    Save All Settings
                </button>
            </div>
        </form>
    </div>
@endsection