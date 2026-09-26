@extends('superadmin.layout')

@section('title', 'Blog Posts')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg text-gray-600">Manage your blog posts</h2>
        </div>
        <a href="{{ route('superadmin.posts.create') }}"
            class="px-4 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
            <i class="fas fa-plus mr-2"></i>New Post
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($posts as $post)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($post->featured_image)
                                        <img src="{{ asset('storage/' . $post->featured_image) }}"
                                            class="w-10 h-10 rounded object-cover mr-3">
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $post->title }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $post->author->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $post->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($post->is_published)
                                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank"
                                            class="p-2 text-gray-400 hover:text-gray-600" title="View">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('superadmin.posts.edit', $post) }}"
                                        class="p-2 text-gray-400 hover:text-sky-500" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('superadmin.posts.destroy', $post) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Delete this post?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-500" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-newspaper text-4xl text-gray-300 mb-4"></i>
                                <p>No posts yet</p>
                                <a href="{{ route('superadmin.posts.create') }}"
                                    class="mt-2 inline-block text-sky-500 hover:text-sky-600">Create your first post</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection