<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('local')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(\Illuminate\Support\Facades\Request::root());
        }

        Paginator::useBootstrapFive();

        $this->configureRateLimiting();

        // Minimum password policy for registration, resets and profile changes.
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->mixedCase()->numbers()->max(128);

            // Checks the HaveIBeenPwned k-anonymity API; skipped outside production to keep dev offline-friendly.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        Blade::directive('money', function ($expression) {
            return "<?php echo number_format($expression, 2); ?>";
        });

        // Use try-catch to avoid issues during migration or if table doesn't exist yet
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $settings = \App\Models\SystemSetting::getMany([
                    'mail_mailer',
                    'mail_host',
                    'mail_port',
                    'mail_username',
                    'mail_password',
                    'mail_encryption',
                    'mail_from_address',
                    'mail_from_name'
                ]);

                if (!empty($settings['mail_host'])) {
                    config([
                        'mail.default' => $settings['mail_mailer'] ?? 'smtp',
                        'mail.mailers.smtp.host' => $settings['mail_host'],
                        'mail.mailers.smtp.port' => $settings['mail_port'],
                        'mail.mailers.smtp.username' => $settings['mail_username'],
                        'mail.mailers.smtp.password' => $settings['mail_password'],
                        'mail.mailers.smtp.encryption' => $settings['mail_encryption'],
                        'mail.from.address' => $settings['mail_from_address'],
                        'mail.from.name' => $settings['mail_from_name'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AppServiceProvider Mail Config Error: ' . $e->getMessage());
        }
    }

    /**
     * Named rate limiters used by the auth and public routes (see routes/web.php).
     * Per-account lockout for failed logins lives in the login controllers themselves.
     */
    protected function configureRateLimiting(): void
    {
        $tooMany = fn (string $message) => function (Request $request, array $headers) use ($message) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => $message])
                ->withHeaders($headers);
        };

        // Login (tenant + superadmin): per IP overall, and per IP+email.
        RateLimiter::for('login', function (Request $request) use ($tooMany) {
            $email = Str::lower((string) $request->input('email'));
            $response = $tooMany('Too many login attempts. Please wait a minute and try again.');

            return [
                Limit::perMinute(20)->by('login-ip:' . $request->ip())->response($response),
                Limit::perMinute(5)->by('login:' . $email . '|' . $request->ip())->response($response),
            ];
        });

        // Registration: stop mass account creation from a single source.
        RateLimiter::for('register', function (Request $request) use ($tooMany) {
            $response = $tooMany('Too many registration attempts. Please try again later.');

            return [
                Limit::perMinute(3)->by('register-min:' . $request->ip())->response($response),
                Limit::perHour(10)->by('register-hour:' . $request->ip())->response($response),
            ];
        });

        // Password reset: prevents mail-bombing a victim's inbox and token brute force.
        RateLimiter::for('password-reset', function (Request $request) use ($tooMany) {
            $email = Str::lower((string) $request->input('email'));
            $response = $tooMany('Too many password reset requests. Please try again later.');

            return [
                Limit::perMinute(5)->by('pwreset-ip:' . $request->ip())->response($response),
                Limit::perHour(5)->by('pwreset-email:' . $email)->response($response),
            ];
        });

        RateLimiter::for('contact', function (Request $request) {
            return [
                Limit::perMinute(2)->by('contact-min:' . $request->ip()),
                Limit::perDay(20)->by('contact-day:' . $request->ip()),
            ];
        });

        RateLimiter::for('public-actions', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('installer', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
