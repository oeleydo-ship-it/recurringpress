<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicQuotationController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\AutoInstallController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ExpenseController;

// Installation routes - using AutoInstallController for fresh installs
Route::middleware(['not.installed', 'throttle:installer'])->prefix('install')->name('install.')->group(function () {
    Route::get('/', [AutoInstallController::class, 'detect'])->name('index');
    Route::get('/requirements', [AutoInstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [AutoInstallController::class, 'database'])->name('database');
    Route::post('/database', [AutoInstallController::class, 'setupDatabase'])->name('setup-database');
    Route::get('/application', [AutoInstallController::class, 'application'])->name('application');
    Route::post('/application', [AutoInstallController::class, 'setupApplication'])->name('setup-application');
    Route::get('/admin', [AutoInstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [AutoInstallController::class, 'setupAdmin'])->name('setup-admin');
    Route::get('/install', [AutoInstallController::class, 'showInstall'])->name('install');
    Route::post('/install', [AutoInstallController::class, 'runInstall'])->name('run-install');
    Route::get('/complete', [AutoInstallController::class, 'complete'])->name('complete');
    Route::post('/test-connection', [AutoInstallController::class, 'testConnection'])->name('test-connection');
});

// Autoinstaller routes (for apps with install.json manifest).
// SECURITY: must be locked once installed — runInstall() drops every table and
// setupDatabase() rewrites .env DB credentials.
Route::middleware(['not.installed', 'throttle:installer'])->prefix('auto-install')->name('autoinstall.')->group(function () {
    Route::get('/', [AutoInstallController::class, 'detect'])->name('detect');
    Route::get('/requirements', [AutoInstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [AutoInstallController::class, 'database'])->name('database');
    Route::post('/database', [AutoInstallController::class, 'setupDatabase'])->name('setup-database');
    Route::get('/application', [AutoInstallController::class, 'application'])->name('application');
    Route::post('/application', [AutoInstallController::class, 'setupApplication'])->name('setup-application');
    Route::get('/admin', [AutoInstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [AutoInstallController::class, 'setupAdmin'])->name('setup-admin');
    Route::get('/install', [AutoInstallController::class, 'showInstall'])->name('install');
    Route::post('/install', [AutoInstallController::class, 'runInstall'])->name('run-install');
    Route::get('/complete', [AutoInstallController::class, 'complete'])->name('complete');
    Route::post('/test-connection', [AutoInstallController::class, 'testConnection'])->name('test-connection');
});

// Landing pages
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/features', [LandingController::class, 'features'])->name('landing.features');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
Route::get('/about', [LandingController::class, 'about'])->name('landing.about');
Route::get('/contact', [LandingController::class, 'contact'])->name('landing.contact');
Route::post('/contact', [LandingController::class, 'submitContact'])->middleware(['throttle:contact', 'honeypot'])->name('landing.contact.submit');
Route::get('/faq', [LandingController::class, 'faq'])->name('landing.faq');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Public invoice/quotation
Route::get('/invoice/{token}', [PublicInvoiceController::class, 'show'])->name('invoices.public');
Route::get('/invoice/{token}/document', [PublicInvoiceController::class, 'document'])->middleware('throttle:public-actions')->name('invoices.public.document');
Route::get('/invoice/{token}/download', [PublicInvoiceController::class, 'download'])->middleware('throttle:public-actions')->name('invoices.public.download');
Route::match(['get', 'post'], '/invoice/{token}/payment', [PublicInvoiceController::class, 'selectPayment'])->name('invoices.public.payment');
Route::post('/invoice/{token}/stripe/intent', [PublicInvoiceController::class, 'createStripeIntent'])->middleware('throttle:public-actions')->name('invoices.public.stripe.intent');
Route::get('/invoice/{token}/stripe/success', [PublicInvoiceController::class, 'stripeSuccess'])->name('invoices.public.stripe.success');
Route::get('/quotation/{token}', [PublicQuotationController::class, 'show'])->name('quotations.public');
Route::get('/quotation/{token}/document', [PublicQuotationController::class, 'document'])->middleware('throttle:public-actions')->name('quotations.public.document');
Route::get('/quotation/{token}/download', [PublicQuotationController::class, 'download'])->middleware('throttle:public-actions')->name('quotations.public.download');
Route::post('/quotation/{token}/accept', [PublicQuotationController::class, 'accept'])->middleware('throttle:public-actions')->name('quotations.public.accept');
Route::post('/quotation/{token}/reject', [PublicQuotationController::class, 'reject'])->middleware('throttle:public-actions')->name('quotations.public.reject');
Route::post('/quotation/{token}/payment', [PublicQuotationController::class, 'selectPayment'])->name('quotations.public.payment');
Route::post('/quotation/{token}/stripe/intent', [PublicQuotationController::class, 'createStripeIntent'])->middleware('throttle:public-actions')->name('quotations.public.stripe.intent');
Route::get('/quotation/{token}/stripe/success', [PublicQuotationController::class, 'stripeSuccess'])->name('quotations.public.stripe.success');
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])
    ->middleware('throttle:60,1')
    ->name('stripe.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware(['throttle:register', 'honeypot']);

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware(['throttle:password-reset', 'honeypot'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/company/create', [CompanyController::class, 'create'])->name('company.create');
    Route::post('/company', [CompanyController::class, 'store'])->name('company.store');

    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password');
});

// Onboarding routes (must be before dashboard with onboarding middleware)
Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/welcome', [OnboardingController::class, 'welcome'])->name('welcome');
    Route::get('/setup', [OnboardingController::class, 'setup'])->name('setup');
    Route::post('/step/{step}', [OnboardingController::class, 'updateStep'])->name('step');
    Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
});

Route::middleware(['auth', 'tenant', 'verified', \App\Http\Middleware\CheckOnboarding::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class);

    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'email'])->name('invoices.email');
    // POST only: a GET here was CSRF-able (e.g. an <img> tag could record a fake payment).
    Route::post('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('/invoices/{invoice}/payment', [PaymentController::class, 'recordManualPayment'])->name('invoices.payment');
    Route::post('/invoices/{invoice}/stripe', [PaymentController::class, 'createPaymentIntent'])->name('invoices.stripe');

    // AI features (JSON; monthly quota per company, see App\Services\Ai\AiClient)
    Route::post('/ai/insights', [\App\Http\Controllers\AiController::class, 'insights'])->middleware('throttle:10,1')->name('ai.insights');
    Route::post('/ai/line-items', [\App\Http\Controllers\AiController::class, 'lineItems'])->middleware('throttle:20,1')->name('ai.line-items');
    Route::post('/invoices/{invoice}/reminder/draft', [\App\Http\Controllers\AiController::class, 'reminder'])->middleware('throttle:20,1')->name('invoices.reminder.draft');
    Route::post('/invoices/{invoice}/reminder/send', [\App\Http\Controllers\AiController::class, 'sendReminder'])->middleware('throttle:10,1')->name('invoices.reminder.send');

    // Payments/Transactions
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    // Payouts
    Route::get('/payouts', [App\Http\Controllers\PayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/create', [App\Http\Controllers\PayoutController::class, 'create'])->name('payouts.create');
    Route::post('/payouts', [App\Http\Controllers\PayoutController::class, 'store'])->name('payouts.store');
    Route::get('/payouts/{payout}', [App\Http\Controllers\PayoutController::class, 'show'])->name('payouts.show');

    // Receipts
    Route::get('/receipts', [App\Http\Controllers\ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [App\Http\Controllers\ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/download', [App\Http\Controllers\ReceiptController::class, 'download'])->name('receipts.download');
    Route::get('/receipts/{receipt}/preview', [App\Http\Controllers\ReceiptController::class, 'preview'])->name('receipts.preview');

    // Expenses
    Route::resource('expenses', ExpenseController::class);
    Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');

    Route::get('/quotations/export', [QuotationController::class, 'export'])->name('quotations.export');
    Route::resource('quotations', QuotationController::class);
    Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
    Route::post('/quotations/{quotation}/email', [QuotationController::class, 'email'])->name('quotations.email');
    Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept');
    Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    // POST only: converting creates an invoice, so it must be CSRF-protected.
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    Route::get('/quotations/{quotation}/download', [QuotationController::class, 'download'])->name('quotations.download');
    Route::get('/quotations/{quotation}/preview', [QuotationController::class, 'preview'])->name('quotations.preview');
    Route::post('/quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');

    // Recurring Invoices (Subscriptions)
    Route::resource('recurring-invoices', RecurringInvoiceController::class);
    Route::post('/recurring-invoices/{recurring_invoice}/pause', [RecurringInvoiceController::class, 'pause'])->name('recurring-invoices.pause');
    Route::post('/recurring-invoices/{recurring_invoice}/resume', [RecurringInvoiceController::class, 'resume'])->name('recurring-invoices.resume');
    Route::post('/recurring-invoices/{recurring_invoice}/cancel', [RecurringInvoiceController::class, 'cancel'])->name('recurring-invoices.cancel');
    Route::post('/recurring-invoices/{recurring_invoice}/generate', [RecurringInvoiceController::class, 'generateNow'])->name('recurring-invoices.generate');

    Route::middleware('role:owner,admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/subscription', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('settings.subscription');
        Route::post('/settings/subscription/{plan}/change', [App\Http\Controllers\SubscriptionController::class, 'changePlan'])->name('settings.subscription.change');

        // Subscription Payment Routes
        Route::get('/settings/subscription/{plan}/payment', [App\Http\Controllers\SubscriptionController::class, 'showPaymentForm'])->name('settings.subscription.payment');
        Route::post('/settings/subscription/{plan}/intent', [App\Http\Controllers\SubscriptionController::class, 'createPaymentIntent'])->name('settings.subscription.intent');
        Route::get('/settings/subscription/{plan}/success', [App\Http\Controllers\SubscriptionController::class, 'handlePaymentSuccess'])->name('settings.subscription.success');

        Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company');
        Route::put('/settings/invoice', [SettingsController::class, 'updateInvoice'])->name('settings.invoice');
        Route::put('/settings/quotation', [SettingsController::class, 'updateQuotation'])->name('settings.quotation');
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
        Route::put('/settings/payment', [SettingsController::class, 'updatePayment'])->name('settings.payment');
        Route::put('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');
        Route::put('/settings/notes-terms', [SettingsController::class, 'updateNotesTerms'])->name('settings.notes-terms');
        Route::get('/settings/template-preview/{template}', [SettingsController::class, 'previewTemplate'])->name('settings.template.preview');
        Route::put('/settings/stripe', [SettingsController::class, 'updateStripe'])->name('settings.stripe');
        Route::put('/settings/domain', [SettingsController::class, 'updateDomain'])->name('settings.domain');
        Route::post('/settings/domain/verify', [SettingsController::class, 'verifyDomain'])->middleware('throttle:10,1')->name('settings.domain.verify');
        Route::delete('/settings/domain', [SettingsController::class, 'removeDomain'])->name('settings.domain.remove');
        Route::put('/settings/subdomain', [SettingsController::class, 'updateSubdomain'])->middleware('throttle:10,1')->name('settings.subdomain');
        Route::delete('/settings/subdomain', [SettingsController::class, 'removeSubdomain'])->name('settings.subdomain.remove');
        Route::put('/settings/email',[SettingsController::class, 'updateEmail'])->name('settings.email');
        Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->middleware('throttle:6,1')->name('settings.email.test');
    });
});

// Superadmin Routes
use App\Http\Controllers\Superadmin\AccountController as SuperadminAccountController;
use App\Http\Controllers\Superadmin\AuthController as SuperadminAuthController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\PageController as SuperadminPageController;
use App\Http\Controllers\Superadmin\PostController as SuperadminPostController;
use App\Http\Controllers\Superadmin\SettingsController as SuperadminSettingsController;
use App\Http\Controllers\Superadmin\PlanController as SuperadminPlanController;
use App\Http\Controllers\Superadmin\MenuController as SuperadminMenuController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/login', [SuperadminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SuperadminAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(\App\Http\Middleware\SuperadminAuthenticate::class)->group(function () {
        Route::post('/logout', [SuperadminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [SuperadminDashboardController::class, 'index'])->name('dashboard');

        // Own login (name, email, password)
        Route::get('/account', [SuperadminAccountController::class, 'edit'])->name('account');
        Route::put('/account', [SuperadminAccountController::class, 'update'])->middleware('throttle:6,1')->name('account.update');
        Route::put('/account/password', [SuperadminAccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password');
        Route::resource('pages', SuperadminPageController::class)->except(['show']);
        Route::resource('posts', SuperadminPostController::class)->except(['show']);
        Route::resource('plans', SuperadminPlanController::class)->except(['show']);
        Route::resource('users', SuperadminUserController::class)->except(['create', 'store']);

        // Settings routes
        Route::get('/settings/general', [SuperadminSettingsController::class, 'general'])->name('settings.general');
        Route::put('/settings/general', [SuperadminSettingsController::class, 'updateGeneral'])->name('settings.general.update');
        Route::get('/settings/stripe', [SuperadminSettingsController::class, 'stripe'])->name('settings.stripe');
        Route::put('/settings/stripe', [SuperadminSettingsController::class, 'updateStripe'])->name('settings.stripe.update');
        Route::get('/settings/smtp', [SuperadminSettingsController::class, 'smtp'])->name('settings.smtp');
        Route::put('/settings/smtp', [SuperadminSettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('/settings/smtp/test', [SuperadminSettingsController::class, 'sendTestEmail'])->name('settings.smtp.test');
        Route::get('/settings/ai', [SuperadminSettingsController::class, 'ai'])->name('settings.ai');
        Route::put('/settings/ai', [SuperadminSettingsController::class, 'updateAi'])->name('settings.ai.update');
        Route::post('/settings/ai/test', [SuperadminSettingsController::class, 'testAi'])->middleware('throttle:10,1')->name('settings.ai.test');
        Route::post('/settings/ai/models', [SuperadminSettingsController::class, 'aiModels'])->middleware('throttle:20,1')->name('settings.ai.models');
        Route::get('/settings/custom-domains',[SuperadminSettingsController::class, 'customDomains'])->name('settings.custom-domains');
        Route::put('/settings/custom-domains', [SuperadminSettingsController::class, 'updateCustomDomains'])->name('settings.custom-domains.update');
        Route::post('/settings/custom-domains/test', [SuperadminSettingsController::class, 'testCloudflare'])->middleware('throttle:10,1')->name('settings.custom-domains.test');
        Route::get('/settings/payouts', [SuperadminSettingsController::class, 'payouts'])->name('settings.payouts');
        Route::put('/settings/payouts', [SuperadminSettingsController::class, 'updatePayouts'])->name('settings.payouts.update');

        Route::get('/settings/landing', [SuperadminSettingsController::class, 'landing'])->name('settings.landing');
        Route::put('/settings/landing', [SuperadminSettingsController::class, 'updateLanding'])->name('settings.landing.update');

        // Payout management
        Route::get('/payouts', [App\Http\Controllers\Superadmin\PayoutController::class, 'index'])->name('payouts.index');
        Route::get('/payouts/{payout}', [App\Http\Controllers\Superadmin\PayoutController::class, 'show'])->name('payouts.show');
        Route::post('/payouts/{payout}/process', [App\Http\Controllers\Superadmin\PayoutController::class, 'process'])->name('payouts.process');
        Route::post('/payouts/{payout}/complete', [App\Http\Controllers\Superadmin\PayoutController::class, 'complete'])->name('payouts.complete');
        Route::post('/payouts/{payout}/reject', [App\Http\Controllers\Superadmin\PayoutController::class, 'reject'])->name('payouts.reject');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Superadmin\ReportController::class, 'index'])->name('reports.index');

        // Menu management
        Route::get('/menus', [SuperadminMenuController::class, 'index'])->name('menus.index');
        Route::put('/menus', [SuperadminMenuController::class, 'update'])->name('menus.update');

        // Media upload
        Route::post('/media/upload', [App\Http\Controllers\Superadmin\MediaController::class, 'upload'])->name('media.upload');

        // System Updates
        Route::get('/updates', [UpdateController::class, 'index'])->name('updates.index');
        Route::put('/updates/settings', [UpdateController::class, 'settings'])->name('updates.settings');
        Route::post('/updates/check', [UpdateController::class, 'checkForUpdates'])->name('updates.check');
        Route::post('/updates/download', [UpdateController::class, 'downloadAndInstall'])->name('updates.download');
        Route::post('/updates/upload', [UpdateController::class, 'uploadUpdate'])->name('updates.upload');
        Route::get('/updates/backups', [UpdateController::class, 'listBackups'])->name('updates.backups');
        Route::post('/updates/restore', [UpdateController::class, 'restoreBackup'])->name('updates.restore');
        Route::delete('/updates/backup', [UpdateController::class, 'deleteBackup'])->name('updates.backup.delete');
    });
});

// Public CMS Page Route (handles about, contact, and other pages)
Route::get('/page/{slug}', [LandingController::class, 'page'])->name('page.show');

