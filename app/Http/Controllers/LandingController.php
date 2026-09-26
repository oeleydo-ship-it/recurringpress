<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $plans = \App\Models\SubscriptionPlan::active()->ordered()->get();

        return view('landing', compact('plans'));
    }

    public function features()
    {
        return view('landing.features');
    }

    public function pricing()
    {
        $plans = \App\Models\SubscriptionPlan::active()->ordered()->get();
        return view('landing.pricing', compact('plans'));
    }

    public function about()
    {
        // Try to load from CMS, fallback to static view
        $page = CmsPage::where('slug', 'about')->where('is_published', true)->first();
        if ($page) {
            return view('page', compact('page'));
        }
        return view('landing.about');
    }

    public function contact()
    {
        // Try to load from CMS, fallback to static view
        $page = CmsPage::where('slug', 'contact')->where('is_published', true)->first();
        if ($page) {
            return view('page', compact('page'));
        }
        return view('landing.contact');
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc,filter|max:255',
            'subject' => ['required', 'string', 'max:200', 'not_regex:/[\r\n]/'],
            'message' => 'required|string|min:10|max:5000',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to('support@recurringpress.com')
                ->queue((new \App\Mail\ContactFormMail(
                    $request->name,
                    $request->email,
                    $request->subject,
                    $request->message
                ))->onQueue('mail'));

            return back()->with('success', 'Thank you for your message! We\'ll get back to you soon.');
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
            return back()->with('success', 'Thank you for your message! We\'ll get back to you soon.');
        }
    }

    public function faq()
    {
        return view('landing.faq');
    }
}

