@extends('superadmin.layout')

@section('title', 'Pages')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg text-gray-600">Manage your static pages</h2>
        </div>
        <a href="{{ route('superadmin.pages.create') }}"
            class="px-4 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
            <i class="fas fa-plus mr-2"></i>New Page
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pages as $page)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $page->title }}</td>
                            <td class="px-6 py-4 text-gray-600">/page/{{ $page->slug }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $page->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $page->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $page->order }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($page->is_published)
                                        <a href="/page/{{ $page->slug }}" target="_blank"
                                            class="p-2 text-gray-400 hover:text-gray-600" title="View">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('superadmin.pages.edit', $page) }}"
                                        class="p-2 text-gray-400 hover:text-sky-500" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('superadmin.pages.destroy', $page) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Delete this page?')">
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
                                <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                                <p>No pages yet</p>
                                <a href="{{ route('superadmin.pages.create') }}"
                                    class="mt-2 inline-block text-sky-500 hover:text-sky-600">Create your first page</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pages->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $pages->links() }}
            </div>
        @endif
    </div>
@endsection