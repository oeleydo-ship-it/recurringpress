<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A notification in the tenant top-bar bell. Rows are written by App\Services\TenantNotifier;
 * always read them through forUser() so a user only ever sees their own company's rows.
 */
class TenantNotification extends Model
{
    protected $fillable = ['company_id', 'user_id', 'type', 'title', 'body', 'url', 'dedupe_key', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id)->where('company_id', $user->company_id);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'has_link' => filled($this->url),
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
