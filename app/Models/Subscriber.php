<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    protected $fillable = [
        'email', 'lang', 'source', 'gclid',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'landing_page', 'ip', 'unsubscribe_token', 'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $s) {
            $s->unsubscribe_token ??= Str::random(40);
        });
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function unsubscribeUrl(): string
    {
        // Emit the link under the public site (desnipperaar.nl), not the admin
        // host. server.js reverse-proxies /afmelden/<token> back to this app's
        // token-gated route, so the customer never sees admin.desnipperaar.nl.
        // Carry the subscriber's language so the confirmation page renders in it
        // even when the token can no longer be resolved.
        return rtrim(config('desnipperaar.public_url'), '/') . '/afmelden/' . $this->unsubscribe_token
            . '?lang=' . (in_array($this->lang, ['nl', 'en', 'fr', 'es'], true) ? $this->lang : 'nl');
    }
}
