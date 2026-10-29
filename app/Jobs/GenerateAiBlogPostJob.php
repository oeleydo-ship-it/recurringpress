<?php

namespace App\Jobs;

use App\Models\AiBlogRun;
use App\Services\Ai\AiBlogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiBlogPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public array $backoff = [60, 300];

    /**
     * @param  string  $keyword  The SEO topic or target keyword
     * @param  array  $options  Generation options (status, tone, language, author_id, etc.)
     */
    public function __construct(
        public string $keyword,
        public array $options = [],
        public ?int $runId = null
    ) {
        $this->onConnection('blog')->onQueue('ai-blog');
    }

    public function handle(AiBlogService $blogService): void
    {
        $lock = Cache::lock('ai-blog-run:'.($this->runId ?? sha1($this->keyword)), 930);
        if (! $lock->get()) {
            $this->release(60);

            return;
        }
        try {
            $run = $this->runId ? AiBlogRun::findOrFail($this->runId) : null;
            if ($run?->status === 'completed') {
                return;
            }
            $run?->update(['status' => 'running', 'error' => null]);
            DB::transaction(function () use ($blogService, $run) {
                $post = $blogService->generateAndCreate($this->keyword, $this->options);
                $issues = $blogService->lastStyleIssues;
                $run?->update(['status' => 'completed', 'post_id' => $post->id, 'error' => $issues ? 'Saved. Still contains banned wording: ' . implode(', ', $issues) . '. Edit the post before publishing.' : null]);
            });
        } catch (Throwable $e) {
            if (isset($run)) {
                $run?->update(['status' => 'queued', 'error' => 'Generation failed; waiting for retry.']);
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        if ($this->runId) {
            AiBlogRun::whereKey($this->runId)->where('status', '!=', 'completed')->update(['status' => 'failed', 'error' => 'Generation failed. Check the provider connection and AI usage errors, then try again.']);
        }
        Log::error("GenerateAiBlogPostJob failed for keyword '{$this->keyword}': ".$e->getMessage(), [
            'exception' => $e,
        ]);
    }
}
