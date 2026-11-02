<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * A client's portal login (guard "client"). Belongs to exactly one client of one company;
 * the same email can have separate logins at different companies.
 */
class ClientAccount extends Authenticatable
{
    /** Session key holding a fingerprint of the password, so changing it ends other sessions. */
    public const SESSION_FINGERPRINT = 'client_portal_fingerprint';

    public const INVITE_LINK_DAYS = 7;

    public const RESET_LINK_MINUTES = 60;

    protected $fillable = ['company_id', 'client_id', 'email', 'is_enabled'];

    protected $hidden = ['password', 'remember_token', 'setup_token_hash'];

    protected $casts = [
        'password' => 'hashed',
        'is_enabled' => 'boolean',
        'setup_token_expires_at' => 'datetime',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Without the team-user company scope: a team member may be signed in on the same browser. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScopes();
    }

    public function clientIsActive(): bool
    {
        return $this->client !== null && ($this->client->is_active ?? true);
    }

    public function canSignIn(): bool
    {
        return $this->is_enabled && filled($this->password) && $this->clientIsActive();
    }

    /** Issues a single-use link token, replacing any earlier one; only its hash is stored. */
    public function issueSetupToken(\DateTimeInterface $expiresAt): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'setup_token_hash' => self::hashToken($token),
            'setup_token_expires_at' => $expiresAt,
        ])->save();

        return $token;
    }

    public static function findBySetupToken(int $companyId, string $token): ?self
    {
        return self::where('company_id', $companyId)
            ->where('setup_token_hash', self::hashToken($token))
            ->where('setup_token_expires_at', '>', now())
            ->where('is_enabled', true)
            ->first();
    }

    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function sessionFingerprint(): string
    {
        return hash('sha256', (string) $this->password);
    }

    /** Link back to the portal for a client signed in to it while viewing their own document. */
    public static function portalLinkFor(Invoice|Quotation $document): ?string
    {
        /** @var self|null $account */
        $account = Auth::guard('client')->user();

        if (! $account || $account->company_id !== $document->company_id || $account->client_id !== $document->client_id) {
            return null;
        }

        return $document->company->clientPortalOpen() && $account->canSignIn()
            ? route('portal.dashboard', $document->company->client_portal_slug)
            : null;
    }
}
