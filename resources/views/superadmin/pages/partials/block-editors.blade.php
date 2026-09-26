<!-- Spacing Controls (reusable) -->
<div x-show="block.data.spacing" class="flex items-center space-x-4 pt-3 mt-3 border-t border-gray-200">
    <span class="text-xs font-medium text-gray-500">Spacing:</span>
    <div class="flex items-center space-x-2">
        <label class="text-xs text-gray-500">Top</label>
        <input type="number" x-model="block.data.spacing.top" min="0" max="100" step="4" class="w-16 px-2 py-1 border border-gray-300 rounded text-xs">
        <span class="text-xs text-gray-400">px</span>
    </div>
    <div class="flex items-center space-x-2">
        <label class="text-xs text-gray-500">Bottom</label>
        <input type="number" x-model="block.data.spacing.bottom" min="0" max="100" step="4" class="w-16 px-2 py-1 border border-gray-300 rounded text-xs">
        <span class="text-xs text-gray-400">px</span>
    </div>
</div>

<!-- Hero Block Editor -->
<template x-if="block.type === 'hero'">
    <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" x-model="block.data.title" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                <input type="text" x-model="block.data.subtitle" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                <input type="text" x-model="block.data.buttonText" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Button URL</label>
                <input type="text" x-model="block.data.buttonUrl" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Background Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" x-model="block.data.bgColor" class="w-10 h-10 rounded border cursor-pointer">
                    <input type="text" x-model="block.data.bgColor" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Text Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" x-model="block.data.textColor" class="w-10 h-10 rounded border cursor-pointer">
                    <input type="text" x-model="block.data.textColor" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Heading Block Editor -->
<template x-if="block.type === 'heading'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Heading Text</label>
            <input type="text" x-model="block.data.text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                <select x-model="block.data.level" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5">H5</option>
                    <option value="h6">H6</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Family</label>
                <select x-model="block.data.fontFamily" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="Inter">Inter</option>
                    <option value="Arial">Arial</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Roboto">Roboto</option>
                    <option value="Open Sans">Open Sans</option>
                    <option value="Lato">Lato</option>
                    <option value="Montserrat">Montserrat</option>
                    <option value="Poppins">Poppins</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Size</label>
                <div class="flex items-center space-x-1">
                    <input type="number" x-model="block.data.fontSize" min="12" max="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <span class="text-xs text-gray-400">px</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Weight</label>
                <select x-model="block.data.fontWeight" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="normal">Normal</option>
                    <option value="medium">Medium</option>
                    <option value="semibold">Semi Bold</option>
                    <option value="bold">Bold</option>
                    <option value="extrabold">Extra Bold</option>
                </select>
            </div>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alignment</label>
                <select x-model="block.data.align" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Text Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" x-model="block.data.color" class="w-10 h-10 rounded border cursor-pointer">
                    <input type="text" x-model="block.data.color" class="flex-1 px-2 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Letter Spacing</label>
                <div class="flex items-center space-x-1">
                    <input type="number" x-model="block.data.letterSpacing" min="-5" max="20" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <span class="text-xs text-gray-400">px</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Line Height</label>
                <select x-model="block.data.lineHeight" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="1">Tight (1)</option>
                    <option value="1.25">Snug (1.25)</option>
                    <option value="1.5">Normal (1.5)</option>
                    <option value="1.75">Relaxed (1.75)</option>
                    <option value="2">Loose (2)</option>
                </select>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" x-model="block.data.uppercase" class="w-4 h-4 text-sky-500 border-gray-300 rounded">
                <span class="text-xs text-gray-600">Uppercase</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" x-model="block.data.italic" class="w-4 h-4 text-sky-500 border-gray-300 rounded">
                <span class="text-xs text-gray-600">Italic</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" x-model="block.data.underline" class="w-4 h-4 text-sky-500 border-gray-300 rounded">
                <span class="text-xs text-gray-600">Underline</span>
            </label>
        </div>
    </div>
</template>

<!-- Text Block Editor -->
<template x-if="block.type === 'text'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Content</label>
            <textarea x-model="block.data.content" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Family</label>
                <select x-model="block.data.fontFamily" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="Inter">Inter</option>
                    <option value="Arial">Arial</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Roboto">Roboto</option>
                    <option value="Open Sans">Open Sans</option>
                    <option value="Lato">Lato</option>
                    <option value="Montserrat">Montserrat</option>
                    <option value="Poppins">Poppins</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Size</label>
                <div class="flex items-center space-x-1">
                    <input type="number" x-model="block.data.fontSize" min="10" max="48" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <span class="text-xs text-gray-400">px</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Font Weight</label>
                <select x-model="block.data.fontWeight" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="normal">Normal</option>
                    <option value="medium">Medium</option>
                    <option value="semibold">Semi Bold</option>
                    <option value="bold">Bold</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alignment</label>
                <select x-model="block.data.align" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                    <option value="justify">Justify</option>
                </select>
            </div>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Text Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" x-model="block.data.color" class="w-10 h-10 rounded border cursor-pointer">
                    <input type="text" x-model="block.data.color" class="flex-1 px-2 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Line Height</label>
                <select x-model="block.data.lineHeight" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="1.25">Tight (1.25)</option>
                    <option value="1.5">Normal (1.5)</option>
                    <option value="1.75">Relaxed (1.75)</option>
                    <option value="2">Loose (2)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Letter Spacing</label>
                <div class="flex items-center space-x-1">
                    <input type="number" x-model="block.data.letterSpacing" min="-2" max="10" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <span class="text-xs text-gray-400">px</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Max Width</label>
                <select x-model="block.data.maxWidth" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Full Width</option>
                    <option value="640">Small (640px)</option>
                    <option value="768">Medium (768px)</option>
                    <option value="1024">Large (1024px)</option>
                </select>
            </div>
        </div>
    </div>
</template>

<!-- Image Block Editor -->
<template x-if="block.type === 'image'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Upload Image</label>
            <div class="flex items-center space-x-3">
                <label class="flex items-center px-4 py-2 bg-sky-500 text-white rounded-lg cursor-pointer hover:bg-sky-600 transition text-sm">
                    <i class="fas fa-upload mr-2"></i> Choose File
                    <input type="file" accept="image/*" class="hidden" @change="uploadImage($event, block)">
                </label>
                <span x-show="block.data.uploading" class="text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading...</span>
            </div>
            <div x-show="block.data.url" class="mt-2">
                <img :src="block.data.url" class="max-h-32 rounded border">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Or Enter Image URL</label>
            <input type="text" x-model="block.data.url" placeholder="https://example.com/image.jpg" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alt Text</label>
                <input type="text" x-model="block.data.alt" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Caption</label>
                <input type="text" x-model="block.data.caption" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Width (%)</label>
                <input type="number" x-model="block.data.width" min="10" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alignment</label>
                <select x-model="block.data.align" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
        </div>
    </div>
</template>

<!-- Button Block Editor -->
<template x-if="block.type === 'button'">
    <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                <input type="text" x-model="block.data.text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">URL</label>
                <input type="text" x-model="block.data.url" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Style</label>
                <select x-model="block.data.style" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="primary">Primary</option>
                    <option value="secondary">Secondary</option>
                    <option value="outline">Outline</option>
                    <option value="ghost">Ghost</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alignment</label>
                <select x-model="block.data.align" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
        </div>
    </div>
</template>

<!-- Features Block Editor -->
<template x-if="block.type === 'features'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Columns</label>
            <select x-model="block.data.columns" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
            </select>
        </div>
        <div class="space-y-3">
            <template x-for="(item, itemIndex) in block.data.items" :key="itemIndex">
                <div class="p-3 bg-white border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500">Feature <span x-text="itemIndex + 1"></span></span>
                        <button type="button" @click="block.data.items.splice(itemIndex, 1)" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="grid md:grid-cols-3 gap-2">
                        <input type="text" x-model="item.icon" placeholder="fas fa-star" class="px-2 py-1 border border-gray-300 rounded text-sm">
                        <input type="text" x-model="item.title" placeholder="Title" class="px-2 py-1 border border-gray-300 rounded text-sm">
                        <input type="text" x-model="item.description" placeholder="Description" class="px-2 py-1 border border-gray-300 rounded text-sm">
                    </div>
                </div>
            </template>
        </div>
        <button type="button" @click="block.data.items.push({ icon: 'fas fa-check', title: 'New Feature', description: 'Description' })" 
                class="text-sm text-sky-600 hover:text-sky-700">
            <i class="fas fa-plus mr-1"></i> Add Feature
        </button>
    </div>
</template>

<!-- CTA Block Editor -->
<template x-if="block.type === 'cta'">
    <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" x-model="block.data.title" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                <input type="text" x-model="block.data.subtitle" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                <input type="text" x-model="block.data.buttonText" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Button URL</label>
                <input type="text" x-model="block.data.buttonUrl" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Background Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" x-model="block.data.bgColor" class="w-8 h-8 rounded border cursor-pointer">
                    <input type="text" x-model="block.data.bgColor" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm">
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Spacer Block Editor -->
<template x-if="block.type === 'spacer'">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Height (px)</label>
        <input type="number" x-model="block.data.height" min="10" max="200" step="10" class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm">
    </div>
</template>

<!-- Divider Block Editor -->
<template x-if="block.type === 'divider'">
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Style</label>
            <select x-model="block.data.style" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="solid">Solid</option>
                <option value="dashed">Dashed</option>
                <option value="dotted">Dotted</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
            <div class="flex items-center space-x-2">
                <input type="color" x-model="block.data.color" class="w-10 h-10 rounded border cursor-pointer">
                <input type="text" x-model="block.data.color" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
    </div>
</template>

<!-- Video Block Editor -->
<template x-if="block.type === 'video'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Video URL (YouTube or Vimeo)</label>
            <input type="text" x-model="block.data.url" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Platform</label>
            <select x-model="block.data.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="youtube">YouTube</option>
                <option value="vimeo">Vimeo</option>
            </select>
        </div>
    </div>
</template>

<!-- Testimonial Block Editor -->
<template x-if="block.type === 'testimonial'">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Quote</label>
            <textarea x-model="block.data.quote" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Author Name</label>
                <input type="text" x-model="block.data.author" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Role / Company</label>
                <input type="text" x-model="block.data.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Avatar URL</label>
                <input type="text" x-model="block.data.avatar" placeholder="https://..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
    </div>
</template>

<!-- Pricing Block Editor -->
<template x-if="block.type === 'pricing'">
    <div class="space-y-4">
        <template x-for="(plan, planIndex) in block.data.plans" :key="planIndex">
            <div class="p-4 bg-white border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700">Plan <span x-text="planIndex + 1"></span></span>
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center text-xs">
                            <input type="checkbox" x-model="plan.popular" class="mr-1"> Popular
                        </label>
                        <button type="button" @click="block.data.plans.splice(planIndex, 1)" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="grid md:grid-cols-4 gap-3 mb-3">
                    <input type="text" x-model="plan.name" placeholder="Plan Name" class="px-2 py-1 border border-gray-300 rounded text-sm">
                    <input type="text" x-model="plan.price" placeholder="29" class="px-2 py-1 border border-gray-300 rounded text-sm">
                    <input type="text" x-model="plan.period" placeholder="month" class="px-2 py-1 border border-gray-300 rounded text-sm">
                    <input type="text" x-model="plan.buttonText" placeholder="Choose Plan" class="px-2 py-1 border border-gray-300 rounded text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Features (one per line)</label>
                    <textarea x-model="plan.featuresText" 
                              @input="plan.features = $event.target.value.split('\n').filter(f => f.trim())"
                              x-init="plan.featuresText = plan.features.join('\n')"
                              rows="3" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
                </div>
            </div>
        </template>
        <button type="button" @click="block.data.plans.push({ name: 'New Plan', price: '19', period: 'month', features: ['Feature 1'], buttonText: 'Choose', buttonUrl: '#' })" 
                class="text-sm text-sky-600 hover:text-sky-700">
            <i class="fas fa-plus mr-1"></i> Add Plan
        </button>
    </div>
</template>

<!-- 2 Columns Block Editor -->
<template x-if="block.type === 'columns'">
    <div class="space-y-4">
        <div class="flex items-center space-x-4 mb-4">
            <label class="text-xs font-medium text-gray-600">Gap between columns:</label>
            <input type="number" x-model="block.data.gap" min="0" max="64" step="4" class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
            <span class="text-xs text-gray-400">px</span>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <!-- Left Column -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 min-h-[150px]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase">Left Column</span>
                    <div class="flex flex-wrap gap-1">
                        <button type="button" @click="addColumnItem(block, 'left', 'heading')" class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded hover:bg-blue-100" title="Heading">
                            <i class="fas fa-heading"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'text')" class="px-2 py-1 text-xs bg-gray-50 text-gray-600 rounded hover:bg-gray-100" title="Text">
                            <i class="fas fa-align-left"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'image')" class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded hover:bg-green-100" title="Image">
                            <i class="fas fa-image"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'button')" class="px-2 py-1 text-xs bg-purple-50 text-purple-600 rounded hover:bg-purple-100" title="Button">
                            <i class="fas fa-mouse-pointer"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'list')" class="px-2 py-1 text-xs bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100" title="List">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'icon')" class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded hover:bg-yellow-100" title="Icon Box">
                            <i class="fas fa-icons"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'video')" class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded hover:bg-red-100" title="Video">
                            <i class="fas fa-video"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'spacer')" class="px-2 py-1 text-xs bg-gray-50 text-gray-400 rounded hover:bg-gray-100" title="Spacer">
                            <i class="fas fa-arrows-alt-v"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'left', 'divider')" class="px-2 py-1 text-xs bg-gray-50 text-gray-400 rounded hover:bg-gray-100" title="Divider">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="(item, idx) in block.data.left" :key="item.id">
                        <div class="bg-white border border-gray-200 rounded p-2">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-gray-500 capitalize" x-text="item.type"></span>
                                <div class="flex space-x-1">
                                    <button type="button" @click="moveColumnItem(block, 'left', idx, -1)" :disabled="idx === 0" class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs"><i class="fas fa-chevron-up"></i></button>
                                    <button type="button" @click="moveColumnItem(block, 'left', idx, 1)" :disabled="idx === block.data.left.length - 1" class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs"><i class="fas fa-chevron-down"></i></button>
                                    <button type="button" @click="removeColumnItem(block, 'left', idx)" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <template x-if="item.type === 'heading'">
                                <input type="text" x-model="item.data.text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Heading text">
                            </template>
                            <template x-if="item.type === 'text'">
                                <textarea x-model="item.data.content" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Text content"></textarea>
                            </template>
                            <template x-if="item.type === 'image'">
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <label class="flex items-center px-2 py-1 bg-sky-500 text-white rounded cursor-pointer hover:bg-sky-600 text-xs">
                                            <i class="fas fa-upload mr-1"></i> Upload
                                            <input type="file" accept="image/*" class="hidden" @change="uploadColumnImage($event, item)">
                                        </label>
                                        <span x-show="item.data.uploading" class="text-xs text-gray-500"><i class="fas fa-spinner fa-spin"></i></span>
                                    </div>
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Or enter URL">
                                    <img x-show="item.data.url" :src="item.data.url" class="max-h-20 rounded">
                                </div>
                            </template>
                            <template x-if="item.type === 'button'">
                                <div class="space-y-2">
                                    <input type="text" x-model="item.data.text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Button text">
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Button URL">
                                    <div class="flex items-center space-x-2">
                                        <select x-model="item.data.style" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="primary">Primary</option>
                                            <option value="secondary">Secondary</option>
                                            <option value="outline">Outline</option>
                                            <option value="success">Success</option>
                                            <option value="danger">Danger</option>
                                        </select>
                                        <select x-model="item.data.size" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="sm">Small</option>
                                            <option value="md">Medium</option>
                                            <option value="lg">Large</option>
                                        </select>
                                        <label class="flex items-center text-xs">
                                            <input type="checkbox" x-model="item.data.fullWidth" class="mr-1"> Full Width
                                        </label>
                                    </div>
                                </div>
                            </template>
                            <template x-if="item.type === 'list'">
                                <div class="space-y-2">
                                    <textarea x-model="item.data.items" rows="3" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="One item per line"></textarea>
                                    <div class="flex items-center space-x-2">
                                        <select x-model="item.data.style" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="bullet">Bullet</option>
                                            <option value="number">Numbered</option>
                                            <option value="check">Checkmarks</option>
                                            <option value="arrow">Arrows</option>
                                        </select>
                                        <input type="color" x-model="item.data.iconColor" class="w-6 h-6 rounded border cursor-pointer">
                                    </div>
                                </div>
                            </template>
                            <template x-if="item.type === 'icon'">
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="text" x-model="item.data.icon" class="w-32 px-2 py-1 border border-gray-300 rounded text-sm" placeholder="fas fa-star">
                                        <input type="color" x-model="item.data.iconColor" class="w-8 h-8 rounded border cursor-pointer">
                                    </div>
                                    <input type="text" x-model="item.data.title" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Title">
                                    <textarea x-model="item.data.description" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Description"></textarea>
                                </div>
                            </template>
                            <template x-if="item.type === 'video'">
                                <div class="space-y-2">
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="YouTube or Vimeo URL">
                                    <select x-model="item.data.aspectRatio" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                        <option value="16:9">16:9</option>
                                        <option value="4:3">4:3</option>
                                        <option value="1:1">Square</option>
                                    </select>
                                </div>
                            </template>
                            <template x-if="item.type === 'spacer'">
                                <div class="flex items-center space-x-2">
                                    <input type="number" x-model="item.data.height" min="10" max="200" step="5" class="w-16 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <span class="text-xs text-gray-400">px</span>
                                    <select x-model="item.data.bgColor" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                        <option value="transparent">Transparent</option>
                                        <option value="#ffffff">White</option>
                                        <option value="#f9fafb">Light Gray</option>
                                    </select>
                                </div>
                            </template>
                            <template x-if="item.type === 'divider'">
                                <div class="text-xs text-gray-400 text-center py-1">― Horizontal Line ―</div>
                            </template>
                        </div>
                    </template>
                    <template x-if="block.data.left.length === 0">
                        <p class="text-xs text-gray-400 text-center py-4">Click buttons above to add content</p>
                    </template>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 min-h-[150px]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase">Right Column</span>
                    <div class="flex flex-wrap gap-1">
                        <button type="button" @click="addColumnItem(block, 'right', 'heading')" class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded hover:bg-blue-100" title="Heading">
                            <i class="fas fa-heading"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'text')" class="px-2 py-1 text-xs bg-gray-50 text-gray-600 rounded hover:bg-gray-100" title="Text">
                            <i class="fas fa-align-left"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'image')" class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded hover:bg-green-100" title="Image">
                            <i class="fas fa-image"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'button')" class="px-2 py-1 text-xs bg-purple-50 text-purple-600 rounded hover:bg-purple-100" title="Button">
                            <i class="fas fa-mouse-pointer"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'list')" class="px-2 py-1 text-xs bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100" title="List">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'icon')" class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded hover:bg-yellow-100" title="Icon Box">
                            <i class="fas fa-icons"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'video')" class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded hover:bg-red-100" title="Video">
                            <i class="fas fa-video"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'spacer')" class="px-2 py-1 text-xs bg-gray-50 text-gray-400 rounded hover:bg-gray-100" title="Spacer">
                            <i class="fas fa-arrows-alt-v"></i>
                        </button>
                        <button type="button" @click="addColumnItem(block, 'right', 'divider')" class="px-2 py-1 text-xs bg-gray-50 text-gray-400 rounded hover:bg-gray-100" title="Divider">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="(item, idx) in block.data.right" :key="item.id">
                        <div class="bg-white border border-gray-200 rounded p-2">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-gray-500 capitalize" x-text="item.type"></span>
                                <div class="flex space-x-1">
                                    <button type="button" @click="moveColumnItem(block, 'right', idx, -1)" :disabled="idx === 0" class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs"><i class="fas fa-chevron-up"></i></button>
                                    <button type="button" @click="moveColumnItem(block, 'right', idx, 1)" :disabled="idx === block.data.right.length - 1" class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs"><i class="fas fa-chevron-down"></i></button>
                                    <button type="button" @click="removeColumnItem(block, 'right', idx)" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <template x-if="item.type === 'heading'">
                                <input type="text" x-model="item.data.text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Heading text">
                            </template>
                            <template x-if="item.type === 'text'">
                                <textarea x-model="item.data.content" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Text content"></textarea>
                            </template>
                            <template x-if="item.type === 'image'">
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <label class="flex items-center px-2 py-1 bg-sky-500 text-white rounded cursor-pointer hover:bg-sky-600 text-xs">
                                            <i class="fas fa-upload mr-1"></i> Upload
                                            <input type="file" accept="image/*" class="hidden" @change="uploadColumnImage($event, item)">
                                        </label>
                                        <span x-show="item.data.uploading" class="text-xs text-gray-500"><i class="fas fa-spinner fa-spin"></i></span>
                                    </div>
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Or enter URL">
                                    <img x-show="item.data.url" :src="item.data.url" class="max-h-20 rounded">
                                </div>
                            </template>
                            <template x-if="item.type === 'button'">
                                <div class="space-y-2">
                                    <input type="text" x-model="item.data.text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Button text">
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Button URL">
                                    <div class="flex items-center space-x-2">
                                        <select x-model="item.data.style" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="primary">Primary</option>
                                            <option value="secondary">Secondary</option>
                                            <option value="outline">Outline</option>
                                            <option value="success">Success</option>
                                            <option value="danger">Danger</option>
                                        </select>
                                        <select x-model="item.data.size" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="sm">Small</option>
                                            <option value="md">Medium</option>
                                            <option value="lg">Large</option>
                                        </select>
                                        <label class="flex items-center text-xs">
                                            <input type="checkbox" x-model="item.data.fullWidth" class="mr-1"> Full Width
                                        </label>
                                    </div>
                                </div>
                            </template>
                            <template x-if="item.type === 'list'">
                                <div class="space-y-2">
                                    <textarea x-model="item.data.items" rows="3" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="One item per line"></textarea>
                                    <div class="flex items-center space-x-2">
                                        <select x-model="item.data.style" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="bullet">Bullet</option>
                                            <option value="number">Numbered</option>
                                            <option value="check">Checkmarks</option>
                                            <option value="arrow">Arrows</option>
                                        </select>
                                        <input type="color" x-model="item.data.iconColor" class="w-6 h-6 rounded border cursor-pointer">
                                    </div>
                                </div>
                            </template>
                            <template x-if="item.type === 'icon'">
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="text" x-model="item.data.icon" class="w-32 px-2 py-1 border border-gray-300 rounded text-sm" placeholder="fas fa-star">
                                        <input type="color" x-model="item.data.iconColor" class="w-8 h-8 rounded border cursor-pointer">
                                    </div>
                                    <input type="text" x-model="item.data.title" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Title">
                                    <textarea x-model="item.data.description" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Description"></textarea>
                                </div>
                            </template>
                            <template x-if="item.type === 'video'">
                                <div class="space-y-2">
                                    <input type="text" x-model="item.data.url" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="YouTube or Vimeo URL">
                                    <select x-model="item.data.aspectRatio" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                        <option value="16:9">16:9</option>
                                        <option value="4:3">4:3</option>
                                        <option value="1:1">Square</option>
                                    </select>
                                </div>
                            </template>
                            <template x-if="item.type === 'spacer'">
                                <div class="flex items-center space-x-2">
                                    <input type="number" x-model="item.data.height" min="10" max="200" step="5" class="w-16 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <span class="text-xs text-gray-400">px</span>
                                    <select x-model="item.data.bgColor" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                        <option value="transparent">Transparent</option>
                                        <option value="#ffffff">White</option>
                                        <option value="#f9fafb">Light Gray</option>
                                    </select>
                                </div>
                            </template>
                            <template x-if="item.type === 'divider'">
                                <div class="text-xs text-gray-400 text-center py-1">― Horizontal Line ―</div>
                            </template>
                        </div>
                    </template>
                    <template x-if="block.data.right.length === 0">
                        <p class="text-xs text-gray-400 text-center py-4">Click buttons above to add content</p>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
