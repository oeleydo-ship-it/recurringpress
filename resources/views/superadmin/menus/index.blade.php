@extends('superadmin.layout')

@section('title', 'Menu Management')

@section('content')
<div x-data="menuManager()" class="max-w-6xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Menu Management</h1>
        <p class="text-gray-600">Manage header and footer navigation menus</p>
    </div>

    <form action="{{ route('superadmin.menus.update') }}" method="POST" @submit="prepareSubmit">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Header Menu -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-bars text-sky-500 mr-2"></i> Header Menu
                </h2>
                <div class="space-y-3" x-ref="headerItems">
                    <template x-for="(item, index) in menus.header" :key="index">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>
                            <input type="text" x-model="item.label" placeholder="Label" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <select x-model="item.type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="url">Custom URL</option>
                                <option value="route">Route</option>
                                <option value="page">CMS Page</option>
                            </select>
                            <input type="text" x-model="item.url" placeholder="URL or Route" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button type="button" @click="removeItem('header', index)" class="p-2 text-red-500 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem('header')" class="mt-3 px-4 py-2 text-sm text-sky-600 hover:bg-sky-50 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Add Menu Item
                </button>
                <input type="hidden" name="menus[0][location]" value="header">
                <input type="hidden" name="menus[0][items]" :value="JSON.stringify(menus.header)">
            </div>

            <!-- Footer Product Menu -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-box text-green-500 mr-2"></i> Footer - Product
                </h2>
                <div class="space-y-3">
                    <template x-for="(item, index) in menus.footer_product" :key="index">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>
                            <input type="text" x-model="item.label" placeholder="Label" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <select x-model="item.type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="url">Custom URL</option>
                                <option value="route">Route</option>
                                <option value="page">CMS Page</option>
                            </select>
                            <input type="text" x-model="item.url" placeholder="URL" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button type="button" @click="removeItem('footer_product', index)" class="p-2 text-red-500 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem('footer_product')" class="mt-3 px-4 py-2 text-sm text-sky-600 hover:bg-sky-50 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Add Menu Item
                </button>
                <input type="hidden" name="menus[1][location]" value="footer_product">
                <input type="hidden" name="menus[1][items]" :value="JSON.stringify(menus.footer_product)">
            </div>

            <!-- Footer Company Menu -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-building text-purple-500 mr-2"></i> Footer - Company
                </h2>
                <div class="space-y-3">
                    <template x-for="(item, index) in menus.footer_company" :key="index">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>
                            <input type="text" x-model="item.label" placeholder="Label" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <select x-model="item.type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="url">Custom URL</option>
                                <option value="route">Route</option>
                                <option value="page">CMS Page</option>
                            </select>
                            <input type="text" x-model="item.url" placeholder="URL" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button type="button" @click="removeItem('footer_company', index)" class="p-2 text-red-500 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem('footer_company')" class="mt-3 px-4 py-2 text-sm text-sky-600 hover:bg-sky-50 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Add Menu Item
                </button>
                <input type="hidden" name="menus[2][location]" value="footer_company">
                <input type="hidden" name="menus[2][items]" :value="JSON.stringify(menus.footer_company)">
            </div>

            <!-- Footer Legal Menu -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-gavel text-orange-500 mr-2"></i> Footer - Legal
                </h2>
                <div class="space-y-3">
                    <template x-for="(item, index) in menus.footer_legal" :key="index">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>
                            <input type="text" x-model="item.label" placeholder="Label" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <select x-model="item.type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="url">Custom URL</option>
                                <option value="route">Route</option>
                                <option value="page">CMS Page</option>
                            </select>
                            <input type="text" x-model="item.url" placeholder="URL" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button type="button" @click="removeItem('footer_legal', index)" class="p-2 text-red-500 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem('footer_legal')" class="mt-3 px-4 py-2 text-sm text-sky-600 hover:bg-sky-50 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Add Menu Item
                </button>
                <input type="hidden" name="menus[3][location]" value="footer_legal">
                <input type="hidden" name="menus[3][items]" :value="JSON.stringify(menus.footer_legal)">
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                Save Menus
            </button>
        </div>
    </form>
</div>

<script>
function menuManager() {
    return {
        menus: {
            header: @json($menus['header']->items ?? []),
            footer_product: @json($menus['footer_product']->items ?? []),
            footer_company: @json($menus['footer_company']->items ?? []),
            footer_legal: @json($menus['footer_legal']->items ?? [])
        },

        addItem(location) {
            this.menus[location].push({ label: '', url: '', type: 'url' });
        },

        removeItem(location, index) {
            this.menus[location].splice(index, 1);
        },

        prepareSubmit() {
            // Items are already bound via x-model
        }
    };
}
</script>
@endsection
