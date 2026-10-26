<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Closure;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Sends client-facing email (invoices, quotations) through the tenant's own SMTP server
 * when it is enabled and has passed a test send, with the tenant's From address.
 *
 * If the tenant's server fails at send time, the failure is logged and stored on the
 * settings (shown in Settings → Email), and the message goes out through the platform
 * mailer instead, with Reply-To set to the company, so the client still gets it.
 */
class TenantMailer
{
    public const ENCRYPTIONS = ['tls', 'ssl', 'none'];

    /**
     * @param  Closure(Message): void  $build  sets recipient, subject, attachments
     * @return string 'tenant' or 'platform' — which mailer delivered it
     */
    public function send(Company $company, string $view, array $data, Closure $build): string
    {
        $settings = $company->settings;

        if ($settings?->usesOwnSmtp()) {
            try {
                $this->mailerFor($settings)->send($view, $data, function (Message $message) use ($settings, $build) {
                    $message->from($settings->smtp_from_address, $settings->smtp_from_name ?: null);
                    $build($message);
                });

                if (filled($settings->smtp_last_error)) {
                    $settings->forceFill(['smtp_last_error' => null, 'smtp_last_error_at' => null])->save();
                }

                return 'tenant';
            } catch (TransportExceptionInterface $e) {
                Log::warning('Tenant SMTP failed; falling back to the platform mailer', [
                    'company_id' => $company->id,
                    'host' => $settings->smtp_host,
                    'error' => $e->getMessage(),
                ]);

                $settings->forceFill([
                    'smtp_last_error' => mb_substr($e->getMessage(), 0, 1000),
                    'smtp_last_error_at' => now(),
                ])->save();
            }
        }

        Mail::send($view, $data, function (Message $message) use ($company, $build) {
            if (filled($company->email)) {
                $message->replyTo($company->email, $company->name);
            }
            $build($message);
        });

        return 'platform';
    }

    /** Send a test message with the saved settings. Throws on any failure. */
    public function sendTest(CompanySetting $settings, string $to): void
    {
        $this->mailerFor($settings)->send('emails.test', [], function (Message $message) use ($settings, $to) {
            $message->from($settings->smtp_from_address, $settings->smtp_from_name ?: null);
            $message->to($to);
            $message->subject('Test email from ' . ($settings->smtp_from_name ?: $settings->smtp_from_address));
        });
    }

    /** A one-off mailer for the tenant's SMTP server (never touches the platform mail config). */
    public function mailerFor(CompanySetting $settings): Mailer
    {
        if ($reason = self::hostRejection((string) $settings->smtp_host, (int) $settings->smtp_port)) {
            throw new TransportException($reason);
        }

        $encryption = $settings->smtp_encryption ?: 'tls';

        return Mail::build([
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings->smtp_host,
            'port' => $settings->smtp_port ?: ($encryption === 'ssl' ? 465 : 587),
            'username' => $settings->smtp_username ?: null,
            'password' => $settings->smtp_password ?: null,
            'timeout' => config('tenancy.smtp.timeout', 15),
            // tls = STARTTLS required; none = plain connection, no opportunistic STARTTLS.
            'require_tls' => $encryption === 'tls',
            'auto_tls' => $encryption !== 'none',
        ]);
    }

    /**
     * Why a host/port may not be used, or null if it's fine. In production, hosts that
     * resolve to private, loopback or reserved addresses are refused (SSRF protection).
     */
    public static function hostRejection(string $host, int $port): ?string
    {
        if (! config('tenancy.smtp.block_private_hosts')) {
            return null;
        }

        if ($port && ! in_array($port, config('tenancy.smtp.allowed_ports', []), true)) {
            return 'Port ' . $port . ' is not allowed. Use one of: ' . implode(', ', config('tenancy.smtp.allowed_ports')) . '.';
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);

        if (! $ips) {
            return "Could not resolve {$host}.";
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return "{$host} resolves to a private or reserved address.";
            }
        }

        return null;
    }
}
