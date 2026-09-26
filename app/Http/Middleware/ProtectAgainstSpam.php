<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight bot protection for public forms (register, contact, forgot-password).
 *
 * Pairs with the <x-honeypot /> Blade component, which renders:
 *  - a visually hidden text field that humans never fill in, and
 *  - an encrypted timestamp of when the form was rendered.
 *
 * A submission is rejected if the honeypot has a value, the timestamp is missing/tampered,
 * the form was submitted faster than a human could fill it, or the form is stale.
 */
class ProtectAgainstSpam
{
    public const HONEYPOT_FIELD = 'contact_me_by_fax_only';
    public const TIMESTAMP_FIELD = '_form_rendered_at';

    public function handle(Request $request, Closure $next, int $minSeconds = 3): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $reason = $this->detectSpam($request, $minSeconds);

        if ($reason !== null) {
            Log::warning('Blocked suspected spam submission', [
                'reason' => $reason,
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
            ]);

            throw ValidationException::withMessages([
                'form' => __('We could not process your submission. Please reload the page and try again.'),
            ]);
        }

        return $next($request);
    }

    protected function detectSpam(Request $request, int $minSeconds): ?string
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        $token = $request->input(self::TIMESTAMP_FIELD);

        if (! is_string($token) || $token === '') {
            return 'missing-timestamp';
        }

        try {
            $renderedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return 'invalid-timestamp';
        }

        $elapsed = time() - $renderedAt;

        if ($elapsed < $minSeconds) {
            return 'too-fast';
        }

        // Forms older than 12 hours are rejected so a harvested token can't be replayed forever.
        if ($elapsed > 43200) {
            return 'expired';
        }

        return null;
    }
}
