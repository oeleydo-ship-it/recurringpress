# AI blog generation and publishing

1. Open Superadmin > AI API configuration. Enable a provider, enter its API key, select a model, save, then use Test connection. Keys remain encrypted and are never sent back to the browser. Blog generation uses the default provider and configured failover order; usage appears as Blog articles.
2. Open Superadmin > Blog posts > Auto-Blogger. Add one topic per line, choose a timezone (for example Asia/Dubai), first daily slot, articles per day, tone, language, FAQ preference, and output status. Save settings with automation enabled.
3. Scheduled articles remain private until their publish time. The editor can change or clear that time. Clearing it saves a draft. Publishing now goes live immediately. The quick generator can use a specific publish time or the next available slot.
4. Recent AI generation shows everything queued or running, plus the latest three finished items. Active jobs refresh every 15 seconds. A failed automatic attempt is logged and uses up its slot; the topic goes behind the others and is tried again once they are done. A job queued from Generate with AI retries once by itself.

Automation runs from the scheduler with nobody signed in. It claims the current time slot, generates the article in the scheduled command, and does not catch up missed slots in a burst. It excludes topics already queued or used, never generates more than the articles-per-day setting in any 24 hours, and pauses when topics run out. Add new topics to continue. Slots are evenly spaced over 24 hours from the chosen start time. Changing timezone or cadence does not reset slots already reserved. Work stuck running for 30 minutes, or queued for an hour, is marked failed so its topic returns to the queue.

## Server processes

Run migrations when deploying: `php artisan migrate --force`.

**Required for automation:** run Laravel's scheduler every minute with cron (`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`) or your host's scheduler setting, or keep `php artisan schedule:work` running on Windows. It generates articles and publishes due posts. Blog posts shows a warning when it has not run for 5 minutes.

Only **Generate with AI** in Queue mode needs a worker. It uses a dedicated Redis queue connection (`blog`, queue `ai-blog`) with a 960-second retry reservation. On Linux the new Horizon supervisor handles it; restart Horizon after deployment with `php artisan horizon:terminate` so the process manager reloads configuration. On Windows or without Horizon, keep this worker running:

```text
php artisan queue:work blog --queue=ai-blog --tries=2 --timeout=900 --sleep=3
```

`start-dev.ps1` now starts this worker alongside the existing local scheduler and application. Redis and MySQL must be running. Use a process manager or Windows Task Scheduler for startup after reboot. Check registered tasks with `php artisan schedule:list`.

The generator validates required article fields, requires a strict JSON schema for supported providers, strips unsafe HTML, creates unique slugs, and tracks completed jobs so a replay does not create another article. Provider calls can fail or incur charges; no API key is included in source code. Live provider verification requires a configured key.
