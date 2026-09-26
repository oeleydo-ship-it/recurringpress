@extends('superadmin.layout')

@section('title', 'Edit Post')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('superadmin.posts.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Posts
            </a>
        </div>

        <form action="{{ route('superadmin.posts.update', $post) }}" method="POST" enctype="multipart/form-data"
            class="bg-white rounded-xl shadow-sm p-6" id="postForm">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                    <div class="flex items-center">
                        <span class="text-gray-500 mr-2">/blog/</span>
                        <input type="text" name="slug" value="{{ old('slug', $post->slug) }}"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                    @if($post->featured_image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $post->featured_image) }}" class="w-32 h-20 object-cover rounded">
                        </div>
                    @endif
                    <input type="file" name="featured_image" accept="image/*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                    <textarea name="excerpt" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <div id="editor" class="bg-white border border-gray-300 rounded-lg">
                        {!! old('content', $post->content) !!}</div>
                    <input type="hidden" name="content" id="content">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                        <input type="text" name="meta_description"
                            value="{{ old('meta_description', $post->meta_description) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}
                        class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                    <label for="is_published" class="ml-2 text-sm text-gray-700">Publish this post</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t">
                <a href="{{ route('superadmin.posts.index') }}"
                    class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit"
                    class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                    Update Post
                </button>
            </div>
        </form>
    </div>

    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Update hidden input on every text change
        quill.on('text-change', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // Also update on form submit as backup
        document.getElementById('postForm').addEventListener('submit', function(e) {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // Initialize content field with current editor content
        if (quill.root.innerHTML && quill.root.innerHTML !== '<p><br></p>') {
            document.getElementById('content').value = quill.root.innerHTML;
        }
    </script>
@endsection