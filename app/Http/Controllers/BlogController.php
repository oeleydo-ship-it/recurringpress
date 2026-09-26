<?php

namespace App\Http\Controllers;

use App\Models\CmsPost;

class BlogController extends Controller
{
    public function index()
    {
        $posts = CmsPost::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        return view('blog.index', compact('posts'));
    }

    public function show($slug)
    {
        $post = CmsPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }
}
