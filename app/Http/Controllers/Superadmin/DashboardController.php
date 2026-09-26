<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\CmsPage;
use App\Models\CmsPost;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_companies' => Company::count(),
            'total_users' => User::count(),
            'total_pages' => CmsPage::count(),
            'total_posts' => CmsPost::count(),
            'published_pages' => CmsPage::where('is_published', true)->count(),
            'published_posts' => CmsPost::where('is_published', true)->count(),
        ];

        $recentCompanies = Company::latest()->take(5)->get();
        $recentPosts = CmsPost::latest()->take(5)->get();

        return view('superadmin.dashboard', compact('stats', 'recentCompanies', 'recentPosts'));
    }
}
