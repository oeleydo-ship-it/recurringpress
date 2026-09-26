<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPagesSeeder extends Seeder
{
    public function run(): void
    {
        // About Us page
        CmsPage::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About Us',
                'content' => '<h2>Our Story</h2>
<p>InvoiceApp was born out of frustration with complicated invoicing software. As freelancers ourselves, we knew there had to be a better way.</p>
<p>We built InvoiceApp to be simple, beautiful, and powerful - everything you need to create professional invoices and get paid faster, without the complexity.</p>
<h2>Our Mission</h2>
<p>Today, thousands of freelancers and small businesses trust InvoiceApp to manage their invoicing and grow their businesses.</p>
<h2>Our Values</h2>
<ul>
<li><strong>Customer First</strong> - Everything we build is designed with our customers in mind.</li>
<li><strong>Simplicity</strong> - We believe powerful software doesn\'t have to be complicated.</li>
<li><strong>Trust & Security</strong> - Your data is safe with us.</li>
</ul>',
                'meta_title' => 'About Us - InvoiceApp',
                'meta_description' => 'Learn about InvoiceApp, our mission, and our values.',
                'is_published' => true,
                'order' => 1,
            ]
        );

        // Contact page
        CmsPage::firstOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact Us',
                'content' => '<h2>Get in Touch</h2>
<p>Have questions? We\'d love to hear from you.</p>
<h3>Email</h3>
<p>support@invoiceapp.com</p>
<h3>Support Hours</h3>
<p>Monday - Friday, 9am - 6pm EST</p>
<h3>Live Chat</h3>
<p>Available for Pro & Enterprise users</p>',
                'meta_title' => 'Contact Us - InvoiceApp',
                'meta_description' => 'Contact the InvoiceApp team for support or sales inquiries.',
                'is_published' => true,
                'order' => 2,
            ]
        );

        // Privacy Policy
        CmsPage::firstOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Privacy Policy',
                'content' => '<h2>Privacy Policy</h2>
<p>Your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your information.</p>
<h3>Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, use our services, or contact us.</p>
<h3>How We Use Information</h3>
<p>We use the information we collect to provide, maintain, and improve our services.</p>',
                'meta_title' => 'Privacy Policy - InvoiceApp',
                'is_published' => true,
                'order' => 3,
            ]
        );

        // Terms of Service
        CmsPage::firstOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Terms of Service',
                'content' => '<h2>Terms of Service</h2>
<p>By using InvoiceApp, you agree to these terms.</p>
<h3>Account Terms</h3>
<p>You must be 18 years or older to use this service. You are responsible for maintaining the security of your account.</p>
<h3>Acceptable Use</h3>
<p>You may not use the service for any illegal or unauthorized purpose.</p>',
                'meta_title' => 'Terms of Service - InvoiceApp',
                'is_published' => true,
                'order' => 4,
            ]
        );
    }
}
