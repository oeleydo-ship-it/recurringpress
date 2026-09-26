@extends('superadmin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-sky-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-sky-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Companies</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_companies'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-green-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-purple-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">CMS Pages</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['published_pages'] }}/{{ $stats['total_pages'] }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-newspaper text-orange-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Blog Posts</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['published_posts'] }}/{{ $stats['total_posts'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recent Companies</h2>
            </div>
            <div class="p-6">
                @forelse($recentCompanies as $company)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">{{ $company->name }}</p>
                            <p class="text-sm text-gray-500">{{ $company->email }}</p>
                        </div>
                        <span class="text-sm text-gray-400">{{ $company->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No companies yet</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Recent Blog Posts</h2>
                <a href="{{ route('superadmin.posts.create') }}" class="text-sky-500 text-sm hover:text-sky-600">
                    + New Post
                </a>
            </div>
            <div class="p-6">
                @forelse($recentPosts as $post)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">{{ $post->title }}</p>
                            <p class="text-sm text-gray-500">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $post->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('superadmin.posts.edit', $post) }}" class="text-gray-400 hover:text-sky-500">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No posts yet</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection