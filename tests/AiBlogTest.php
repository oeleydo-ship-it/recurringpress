<?php

namespace Tests;

use App\Jobs\GenerateAiBlogPostJob;
use App\Models\AiBlogRun;
use App\Models\CmsPost;
use App\Models\SystemSetting;
use App\Services\Ai\AiBlogService;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiProviders;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\TestCase;

class AiBlogTest extends TestCase
{
    private AiBlogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('CACHE_DRIVER=array');
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        restore_error_handler();
        restore_exception_handler();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        foreach (['2024_01_02_000001_create_superadmins_table', '2024_01_02_000003_create_cms_posts_table', '2024_01_02_000004_create_system_settings_table', '2026_10_28_140000_add_ai_fields_to_cms_posts_table', '2026_10_29_000001_create_ai_blog_runs_table'] as $migration) {
            (require __DIR__.'/../database/migrations/'.$migration.'.php')->up();
        }
        SystemSetting::setMany(['ai_enabled' => '1', 'ai_default_provider' => 'openai', 'ai_openai_enabled' => '1', 'ai_openai_model' => 'test-model', 'ai_blog_enabled' => '1', 'ai_blog_keywords' => "First topic\nSecond topic", 'ai_blog_posts_per_day' => '2', 'ai_blog_timezone' => 'Asia/Dubai', 'ai_blog_start_time' => '09:00']);
        AiProviders::storeKey('openai', 'fake-test-key');
        Carbon::setTestNow(Carbon::parse('2026-10-29 06:00:00', 'UTC'));
        Queue::fake();
        $client = $this->createStub(AiClient::class);
        $client->method('json')->willReturn(['title' => 'Invoice tips', 'slug' => 'invoice-tips', 'meta_title' => 'Invoice tips', 'meta_description' => 'Better invoicing habits', 'excerpt' => 'Practical invoicing tips', 'content' => '<h2 onclick="alert(1)">Tips</h2><script>alert(1)</script><p>Helpful article <a href="javascript:alert(1)">link</a></p>', 'suggested_tags' => []]);
        $this->service = new AiBlogService($client);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_reserves_slots_and_topics_before_worker_runs(): void
    {
        self::assertSame('First topic', $this->service->runScheduledGeneration());
        self::assertNull($this->service->runScheduledGeneration());
        Carbon::setTestNow(Carbon::parse('2026-10-29 18:00:00', 'UTC'));
        self::assertSame('Second topic', $this->service->runScheduledGeneration());
        self::assertCount(2, AiBlogRun::all());
        Queue::assertPushed(GenerateAiBlogPostJob::class, 2);
        Carbon::setTestNow(Carbon::parse('2026-10-30 06:00:00', 'UTC'));
        self::assertNull($this->service->runScheduledGeneration());
    }

    public function test_generated_article_is_safe_scheduled_and_published_only_when_due(): void
    {
        $post = $this->service->generateAndCreate('First topic', ['status' => 'scheduled']);
        self::assertFalse($post->is_published);
        self::assertSame('2026-10-29 17:00', $post->published_at->format('Y-m-d H:i'));
        self::assertStringNotContainsString('<script', $post->content);
        self::assertStringNotContainsString('onclick', $post->content);
        self::assertStringNotContainsString('javascript:', $post->content);
        self::assertSame(0, $this->service->publishDuePosts());
        Carbon::setTestNow($post->published_at);
        self::assertSame(1, $this->service->publishDuePosts());
        self::assertSame(0, $this->service->publishDuePosts());
        self::assertTrue($post->fresh()->is_published);
    }

    public function test_drafts_stay_draft_and_slug_collisions_are_resolved(): void
    {
        $first = $this->service->generateAndCreate('First topic', ['status' => 'draft']);
        $second = $this->service->generateAndCreate('Second topic', ['status' => 'draft']);
        self::assertNull($first->published_at);
        self::assertNotSame($first->slug, $second->slug);
        self::assertSame(0, $this->service->publishDuePosts());
    }

    public function test_replayed_job_does_not_create_duplicate_article(): void
    {
        $run = $this->service->queuePost('First topic', ['status' => 'draft']);
        $job = new GenerateAiBlogPostJob($run->keyword, $run->options, $run->id);
        $job->handle($this->service);
        $job->handle($this->service);
        self::assertSame(1, CmsPost::count());
        self::assertSame('completed', $run->fresh()->status);
        self::assertNotNull($run->fresh()->post_id);
    }

    public function test_explicit_schedule_and_midnight_slots_use_selected_timezone(): void
    {
        $post = $this->service->generateAndCreate('First topic', ['status' => 'scheduled', 'published_at' => '2026-10-30T11:00:00+04:00']);
        self::assertSame('2026-10-30 07:00', $post->published_at->format('Y-m-d H:i'));
        $post->delete();
        SystemSetting::set('ai_blog_posts_per_day', '6');
        Carbon::setTestNow(Carbon::parse('2026-10-29 23:00:00', 'UTC'));
        self::assertSame('2026-10-30 01:00', $this->service->calculateNextScheduleTime(6)->format('Y-m-d H:i'));
    }

    public function test_disabling_ai_prevents_automatic_generation(): void
    {
        SystemSetting::set('ai_enabled', '0');
        self::assertNull($this->service->runScheduledGeneration());
        Queue::assertNothingPushed();
    }
    public function test_unpublishing_clears_date_so_scheduler_does_not_republish(): void
    {
        $post = $this->service->generateAndCreate('First topic', ['status' => 'published']);
        $request = \Illuminate\Http\Request::create('/superadmin/posts/'.$post->id, 'PUT', [
            'title' => $post->title, 'slug' => $post->slug, 'is_published' => false, 'published_at' => null,
        ]);
        (new \App\Http\Controllers\Superadmin\PostController)->update($request, $post);
        self::assertNull($post->fresh()->published_at);
        self::assertFalse($post->fresh()->is_published);
        self::assertSame(0, $this->service->publishDuePosts());
    }

    public function test_failure_is_visible_without_exposing_provider_secrets(): void
    {
        $run = $this->service->queuePost('First topic', ['status' => 'draft']);
        $job = new GenerateAiBlogPostJob($run->keyword, $run->options, $run->id);
        $job->failed(new \RuntimeException('secret-api-key'));
        self::assertSame('failed', $run->fresh()->status);
        self::assertStringNotContainsString('secret-api-key', $run->fresh()->error);
    }

}
