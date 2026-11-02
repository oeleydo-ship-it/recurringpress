<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Http\Request;

/** robots.txt and sitemap.xml for platform hosts. Tenant hosts are answered in ResolveCustomDomain. */
class SeoController extends Controller
{
    public function robots(Request $request)
    {
        return response(Seo::robotsTxt($request), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function sitemap(Request $request)
    {
        return response(Seo::sitemapXml($request), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
