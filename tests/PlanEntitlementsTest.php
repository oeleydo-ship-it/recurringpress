<?php

namespace Tests;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Support\TenantDomain;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class PlanEntitlementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        restore_error_handler();
        restore_exception_handler();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        (require __DIR__.'/../database/migrations/2024_01_02_000005_create_subscription_plans_table.php')->up();
        (require __DIR__.'/../database/migrations/2026_10_28_120000_add_plan_entitlements.php')->up();
        (require __DIR__.'/../database/migrations/2026_10_28_130000_add_trial_days_to_subscription_plans.php')->up();
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->string('subscription_status')->default('active');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->string('quotation_prefix')->nullable();
            $table->integer('quotation_validity_days')->nullable();
            $table->timestamps();
        });
        foreach (['clients', 'products', 'users'] as $resource) {
            Schema::create($resource, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('subdomain')->nullable();
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('share_token');
            $table->timestamps();
        });
    }

    public function test_trial_duration_comes_from_the_plan(): void
    {
        $plan = new SubscriptionPlan(['price' => 19, 'trial_days' => 7]);
        $subscription = $plan->initialSubscription();
        self::assertSame('trial', $subscription['subscription_status']);
        self::assertSame(now()->addDays(7)->toDateString(), $subscription['subscription_ends_at']->toDateString());
        $plan->trial_days = 0;
        self::assertSame('expired', $plan->initialSubscription()['subscription_status']);
        $plan->price = 0;
        self::assertSame(['subscription_status' => 'active', 'subscription_ends_at' => null], $plan->initialSubscription());
    }

    private function tenant(array $limits = []): Company
    {
        $plan = SubscriptionPlan::create(['name' => uniqid('Plan'), 'price' => 19, ...$limits]);
        return Company::create(['name' => 'Tenant', 'is_active' => true, 'subscription_status' => 'active', 'subscription_plan_id' => $plan->id]);
    }

    public function test_quota_is_enforced_without_a_controller_or_authenticated_user(): void
    {
        $company = $this->tenant(['max_invoices' => 1]);
        Invoice::create(['company_id' => $company->id]);
        $other = $this->tenant(['max_invoices' => 1]);
        Invoice::create(['company_id' => $other->id]);
        self::assertSame(1, $company->invoices()->count());
        $this->expectException(ValidationException::class);
        Invoice::create(['company_id' => $company->id]);
    }

    public function test_all_resource_models_enforce_their_tenant_quota(): void
    {
        $company = $this->tenant(['max_clients' => 0, 'max_products' => 0, 'max_users' => 0]);
        foreach ([\App\Models\Client::class, \App\Models\Product::class, \App\Models\User::class] as $model) {
            try {
                $model::create(['company_id' => $company->id]);
                self::fail('Creation should be denied for '.$model);
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('plan', $exception->errors());
            }
        }
    }

    public function test_unlimited_and_zero_limits_are_distinct(): void
    {
        $company = $this->tenant(['max_invoices' => null]);
        Invoice::create(['company_id' => $company->id]);
        self::assertFalse($company->hasReachedLimit('invoices'));
        $company->subscriptionPlan->update(['max_invoices' => 0]);
        self::assertTrue($company->hasReachedLimit('invoices'));
    }

    public function test_expiry_disables_entitlements_and_creation(): void
    {
        $company = $this->tenant(['white_label' => true, 'custom_domain' => true, 'subdomain' => true]);
        self::assertTrue($company->canUseFeature('white_label'));
        self::assertFalse($company->canUseFeature('unknown'));
        $company->update(['subscription_ends_at' => now()->subSecond()]);
        foreach (['white_label', 'custom_domain', 'subdomain'] as $feature) {
            self::assertFalse($company->canUseFeature($feature));
        }
        $this->expectException(ValidationException::class);
        Invoice::create(['company_id' => $company->id]);
    }

    public function test_trial_access_ends_at_expiry_and_inactive_tenant_is_denied(): void
    {
        $company = $this->tenant(['custom_branding' => true]);
        $company->update(['subscription_status' => 'trial', 'subscription_ends_at' => now()->addDay()]);
        self::assertTrue($company->canUseFeature('custom_branding'));
        $company->update(['is_active' => false]);
        self::assertFalse($company->canUseFeature('custom_branding'));
    }

    public function test_tenant_root_renders_home_while_documents_keep_their_tenant_binding(): void
    {
        \Illuminate\Support\Facades\Cache::put('system_setting_tenant_subdomains_enabled', '1');
        \Illuminate\Support\Facades\Cache::put('system_setting_custom_domains_enabled', '0');
        \Illuminate\Support\Facades\Cache::put('system_setting_site_name', 'Test Platform');
        config(['app.url' => 'https://billing.test', 'tenancy.subdomains.base' => 'billing.test']);
        $company = $this->tenant(['subdomain' => true, 'white_label' => true]);
        $company->update(['name' => '<script>alert(1)</script>']);
        CompanySetting::create(['company_id' => $company->id])->forceFill(['subdomain' => 'acme'])->save();
        $middleware = new \App\Http\Middleware\ResolveCustomDomain;
        $next = fn ($request) => response('next');
        $request = \Illuminate\Http\Request::create('https://acme.billing.test/');
        $home = $middleware->handle($request, $next);
        self::assertSame(200, $home->getStatusCode());
        self::assertStringContainsString('&lt;script&gt;', $home->getContent());
        self::assertStringNotContainsString('<script>alert(1)</script>', $home->getContent());
        self::assertStringContainsString('https://billing.test/login', $home->getContent());
        self::assertStringNotContainsString('Powered by', $home->getContent());
        self::assertStringContainsString('no-store', $home->headers->get('Cache-Control'));
        $document = \Illuminate\Http\Request::create('https://acme.billing.test/invoice/token');
        self::assertSame('next', $middleware->handle($document, $next)->getContent());
        self::assertSame($company->id, $document->attributes->get(TenantDomain::ATTRIBUTE));
        self::assertSame('next', $middleware->handle(\Illuminate\Http\Request::create('https://billing.test/'), $next)->getContent());
        self::assertSame(404, $middleware->handle(\Illuminate\Http\Request::create('https://unknown.billing.test/'), $next)->getStatusCode());
        self::assertSame(404, $middleware->handle(\Illuminate\Http\Request::create('https://acme.billing.test/', 'POST'), $next)->getStatusCode());
        $company->update(['subscription_ends_at' => now()->subDay()]);
        self::assertSame(404, $middleware->handle($request, $next)->getStatusCode());
    }

    public function test_saved_subdomain_stops_working_after_downgrade(): void
    {
        \Illuminate\Support\Facades\Cache::put('system_setting_tenant_subdomains_enabled', '1');
        \Illuminate\Support\Facades\Cache::put('system_setting_custom_domains_enabled', '0');
        config(['tenancy.subdomains.base' => 'billing.test']);
        $company = $this->tenant(['subdomain' => true]);
        $settings = CompanySetting::create(['company_id' => $company->id]);
        $settings->forceFill(['subdomain' => 'acme'])->save();
        self::assertSame($company->id, TenantDomain::companyForHost('acme.billing.test')?->id);
        $company->subscriptionPlan->update(['subdomain' => false]);
        self::assertNull(TenantDomain::companyForHost('acme.billing.test'));
        self::assertFalse($settings->fresh()->hasActiveSubdomain());
    }
}
