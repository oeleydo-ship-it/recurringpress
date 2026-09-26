@extends('superadmin.layout')

@section('title', 'Edit Page')

@section('content')
<script>
    function pageBuilder() {
        return {
            editorMode: '{{ $page->editor_mode ?? "blocks" }}',
            blocks: @json($page->blocks ?? []) || [],
            blockIdCounter: {{ $page->blocks ? count($page->blocks) : 0 }},

            init() {
                if (!Array.isArray(this.blocks)) {
                    this.blocks = [];
                }
                this.blocks = this.blocks.map((block, index) => {
                    if (!block.id) block.id = index + 1;
                    return block;
                });
                this.blockIdCounter = Math.max(this.blocks.length, 1);
            },

            addBlock(type) {
                const block = {
                    id: ++this.blockIdCounter,
                    type: type,
                    data: this.getDefaultData(type)
                };
                this.blocks.push(block);
            },

            getDefaultData(type) {
                const defaults = {
                    hero: { title: 'Welcome to Our Site', subtitle: 'Your success story starts here', buttonText: 'Get Started', buttonUrl: '#', bgColor: '#0ea5e9', textColor: '#ffffff', spacing: { top: 16, bottom: 16 } },
                    heading: { text: 'Section Heading', level: 'h2', align: 'center', fontFamily: 'Inter', fontSize: '', fontWeight: 'bold', color: '#111827', letterSpacing: 0, lineHeight: '1.25', uppercase: false, italic: false, underline: false, spacing: { top: 8, bottom: 8 } },
                    text: { content: 'Enter your text content here...', align: 'left', fontFamily: 'Inter', fontSize: 16, fontWeight: 'normal', color: '#374151', lineHeight: '1.75', letterSpacing: 0, maxWidth: '', spacing: { top: 4, bottom: 4 } },
                    image: { url: '', alt: '', caption: '', align: 'center', width: '100', spacing: { top: 4, bottom: 4 }, uploading: false },
                    button: { text: 'Click Here', url: '#', style: 'primary', align: 'center', spacing: { top: 4, bottom: 4 } },
                    features: { columns: 3, items: [
                        { icon: 'fas fa-rocket', title: 'Feature 1', description: 'Description here' },
                        { icon: 'fas fa-shield-alt', title: 'Feature 2', description: 'Description here' },
                        { icon: 'fas fa-heart', title: 'Feature 3', description: 'Description here' }
                    ], spacing: { top: 12, bottom: 12 } },
                    cta: { title: 'Ready to get started?', subtitle: 'Join thousands of happy customers', buttonText: 'Sign Up Now', buttonUrl: '#', bgColor: '#1e293b', spacing: { top: 12, bottom: 12 } },
                    spacer: { height: 40 },
                    divider: { style: 'solid', color: '#e5e7eb', spacing: { top: 4, bottom: 4 } },
                    video: { url: '', type: 'youtube', spacing: { top: 8, bottom: 8 } },
                    testimonial: { quote: 'This product changed my life!', author: 'John Doe', role: 'CEO, Company', avatar: '', spacing: { top: 8, bottom: 8 } },
                    pricing: { plans: [
                        { name: 'Basic', price: '9', period: 'month', features: ['Feature 1', 'Feature 2'], buttonText: 'Choose', buttonUrl: '#' },
                        { name: 'Pro', price: '29', period: 'month', features: ['All Basic features', 'Feature 3', 'Feature 4'], buttonText: 'Choose', buttonUrl: '#', popular: true }
                    ], spacing: { top: 12, bottom: 12 } },
                    columns: { gap: 24, left: [], right: [], spacing: { top: 8, bottom: 8 } }
                };
                return JSON.parse(JSON.stringify(defaults[type] || {}));
            },

            getBlockIcon(type) {
                const icons = {
                    hero: 'fas fa-star text-yellow-500',
                    heading: 'fas fa-heading text-blue-500',
                    text: 'fas fa-align-left text-gray-500',
                    image: 'fas fa-image text-green-500',
                    button: 'fas fa-mouse-pointer text-purple-500',
                    features: 'fas fa-th text-indigo-500',
                    cta: 'fas fa-bullhorn text-red-500',
                    spacer: 'fas fa-arrows-alt-v text-gray-400',
                    divider: 'fas fa-minus text-gray-400',
                    video: 'fas fa-video text-red-500',
                    testimonial: 'fas fa-quote-left text-teal-500',
                    pricing: 'fas fa-tags text-orange-500',
                    columns: 'fas fa-columns text-cyan-500'
                };
                return icons[type] || 'fas fa-cube';
            },

            addColumnItem(block, column, type) {
                const itemId = Date.now();
                const defaults = {
                    heading: { text: 'Heading', level: 'h3', align: 'left' },
                    text: { content: 'Text content...', align: 'left' },
                    image: { url: '', alt: '', width: '100', uploading: false },
                    button: { text: 'Click Here', url: '#', style: 'primary', size: 'md', fullWidth: false },
                    list: { items: 'Item 1\nItem 2\nItem 3', style: 'bullet', iconColor: '#0ea5e9' },
                    icon: { icon: 'fas fa-star', iconColor: '#0ea5e9', title: 'Feature Title', description: 'Description text here' },
                    video: { url: '', aspectRatio: '16:9' },
                    spacer: { height: 20, bgColor: 'transparent' },
                    divider: {}
                };
                block.data[column].push({ id: itemId, type: type, data: JSON.parse(JSON.stringify(defaults[type] || {})) });
            },

            removeColumnItem(block, column, index) {
                block.data[column].splice(index, 1);
            },

            moveColumnItem(block, column, index, direction) {
                const newIndex = index + direction;
                if (newIndex >= 0 && newIndex < block.data[column].length) {
                    const temp = block.data[column][index];
                    block.data[column][index] = block.data[column][newIndex];
                    block.data[column][newIndex] = temp;
                }
            },

            async uploadImage(event, block) {
                const file = event.target.files[0];
                if (!file) return;
                block.data.uploading = true;
                const formData = new FormData();
                formData.append('image', file);
                try {
                    const response = await fetch('{{ route("superadmin.media.upload") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) block.data.url = data.url;
                } catch (error) {
                    console.error('Upload failed:', error);
                } finally {
                    block.data.uploading = false;
                }
            },

            async uploadColumnImage(event, item) {
                const file = event.target.files[0];
                if (!file) return;
                item.data.uploading = true;
                const formData = new FormData();
                formData.append('image', file);
                try {
                    const response = await fetch('{{ route("superadmin.media.upload") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) item.data.url = data.url;
                } catch (error) {
                    console.error('Upload failed:', error);
                } finally {
                    item.data.uploading = false;
                }
            },

            moveBlock(index, direction) {
                const newIndex = index + direction;
                if (newIndex >= 0 && newIndex < this.blocks.length) {
                    const temp = this.blocks[index];
                    this.blocks[index] = this.blocks[newIndex];
                    this.blocks[newIndex] = temp;
                }
            },

            duplicateBlock(index) {
                const block = JSON.parse(JSON.stringify(this.blocks[index]));
                block.id = ++this.blockIdCounter;
                this.blocks.splice(index + 1, 0, block);
            },

            removeBlock(index) {
                this.blocks.splice(index, 1);
            },

            prepareSubmit() {
                if (this.editorMode === 'classic' && typeof quill !== 'undefined') {
                    document.getElementById('content').value = quill.root.innerHTML;
                }
                document.getElementById('blocksInput').value = JSON.stringify(this.blocks);
            }
        };
    }
</script>
<div x-data="pageBuilder()" class="max-w-6xl">
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('superadmin.pages.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Pages
        </a>
        <div class="flex items-center space-x-2 bg-gray-100 rounded-lg p-1">
            <button type="button" @click="editorMode = 'classic'" 
                    :class="editorMode === 'classic' ? 'bg-white shadow-sm' : ''"
                    class="px-4 py-2 text-sm font-medium rounded-md transition">
                <i class="fas fa-edit mr-1"></i> Classic
            </button>
            <button type="button" @click="editorMode = 'blocks'"
                    :class="editorMode === 'blocks' ? 'bg-white shadow-sm' : ''"
                    class="px-4 py-2 text-sm font-medium rounded-md transition">
                <i class="fas fa-th-large mr-1"></i> Block Builder
            </button>
        </div>
    </div>

    <form action="{{ route('superadmin.pages.update', $page) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6" id="pageForm" @submit="prepareSubmit">
        @csrf
        @method('PUT')
        <input type="hidden" name="editor_mode" :value="editorMode">
        <input type="hidden" name="blocks" id="blocksInput">
        <input type="hidden" name="content" id="content">

        <div class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                    <div class="flex items-center">
                        <span class="text-gray-500 mr-2">/page/</span>
                        <input type="text" name="slug" value="{{ old('slug', $page->slug) }}"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>

            <!-- Classic Editor -->
            <div x-show="editorMode === 'classic'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                <div id="editor" class="bg-white border border-gray-300 rounded-lg">{!! old('content', $page->content) !!}</div>
            </div>

            <!-- Block Builder -->
            <div x-show="editorMode === 'blocks'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-2">Page Blocks</label>
                
                <!-- Block Types Toolbar -->
                <div class="flex flex-wrap gap-2 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <button type="button" @click="addBlock('hero')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-star text-yellow-500 mr-2"></i> Hero
                    </button>
                    <button type="button" @click="addBlock('heading')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-heading text-blue-500 mr-2"></i> Heading
                    </button>
                    <button type="button" @click="addBlock('text')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-align-left text-gray-500 mr-2"></i> Text
                    </button>
                    <button type="button" @click="addBlock('image')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-image text-green-500 mr-2"></i> Image
                    </button>
                    <button type="button" @click="addBlock('button')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-mouse-pointer text-purple-500 mr-2"></i> Button
                    </button>
                    <button type="button" @click="addBlock('features')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-th text-indigo-500 mr-2"></i> Features
                    </button>
                    <button type="button" @click="addBlock('cta')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-bullhorn text-red-500 mr-2"></i> CTA
                    </button>
                    <button type="button" @click="addBlock('spacer')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-arrows-alt-v text-gray-400 mr-2"></i> Spacer
                    </button>
                    <button type="button" @click="addBlock('divider')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-minus text-gray-400 mr-2"></i> Divider
                    </button>
                    <button type="button" @click="addBlock('video')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-video text-red-500 mr-2"></i> Video
                    </button>
                    <button type="button" @click="addBlock('testimonial')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-quote-left text-teal-500 mr-2"></i> Testimonial
                    </button>
                    <button type="button" @click="addBlock('pricing')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-tags text-orange-500 mr-2"></i> Pricing
                    </button>
                    <button type="button" @click="addBlock('columns')" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                        <i class="fas fa-columns text-cyan-500 mr-2"></i> 2 Columns
                    </button>
                </div>

                <!-- Blocks List -->
                <div class="space-y-4 min-h-[200px] border-2 border-dashed border-gray-300 rounded-lg p-4" 
                     :class="blocks.length === 0 ? 'flex items-center justify-center' : ''">
                    
                    <template x-if="blocks.length === 0">
                        <p class="text-gray-400 text-center">
                            <i class="fas fa-plus-circle text-4xl mb-2 block"></i>
                            Click a block type above to start building your page
                        </p>
                    </template>

                    <template x-for="(block, index) in blocks" :key="block.id">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Block Header -->
                            <div class="flex items-center justify-between px-4 py-2 bg-gray-100 border-b">
                                <div class="flex items-center space-x-2">
                                    <i :class="getBlockIcon(block.type)" class="text-sm"></i>
                                    <span class="font-medium text-sm capitalize" x-text="block.type"></span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <button type="button" @click="moveBlock(index, -1)" :disabled="index === 0" class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <button type="button" @click="moveBlock(index, 1)" :disabled="index === blocks.length - 1" class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <button type="button" @click="duplicateBlock(index)" class="p-1 text-gray-400 hover:text-blue-600">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button" @click="removeBlock(index)" class="p-1 text-gray-400 hover:text-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Block Content Editor -->
                            <div class="p-4">
                                @include('superadmin.pages.partials.block-editors')
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="order" value="{{ old('order', $page->order) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                <textarea name="meta_description" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('meta_description', $page->meta_description) }}</textarea>
            </div>

            <div class="flex items-center space-x-6">
                <div class="flex items-center">
                    <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }}
                           class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                    <label for="is_published" class="ml-2 text-sm text-gray-700">Publish this page</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="show_title" id="show_title" value="1" {{ old('show_title', $page->show_title ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                    <label for="show_title" class="ml-2 text-sm text-gray-700">Show page title</label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t">
            <a href="{{ route('superadmin.pages.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                Update Page
            </button>
        </div>
    </form>
</div>

<script>
    var quill;
    document.addEventListener('DOMContentLoaded', function() {
        quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
    });
</script>
@endsection