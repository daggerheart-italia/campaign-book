<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'system',
        'banner_url',
        'invite_code_player',
        'invite_code_gm',
    ];

    protected static function booted()
    {
        static::creating(function ($campaign) {
            if (empty($campaign->invite_code_player)) {
                $campaign->invite_code_player = Str::random(16);
            }
            if (empty($campaign->invite_code_gm)) {
                $campaign->invite_code_gm = Str::random(16);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function maps(): HasMany
    {
        return $this->hasMany(CampaignMap::class);
    }

    public function wikiPages(): HasMany
    {
        return $this->hasMany(WikiPage::class);
    }

    public function mapPins(): HasMany
    {
        return $this->hasMany(MapPin::class);
    }

    public function sessionNotes(): HasMany
    {
        return $this->hasMany(SessionNote::class)->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Check if user is a GM of this campaign.
     */
    public function isGm(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->users()
            ->where('user_id', $user->id)
            ->where('role', 'gm')
            ->exists();
    }

    /**
     * Check if user is a member (GM or Player).
     */
    public function isMember(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Get user role in campaign.
     */
    public function roleOf(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($this->owner_id === $user->id) {
            return 'gm';
        }

        $pivot = $this->users()->where('user_id', $user->id)->first();
        return $pivot ? $pivot->pivot->role : null;
    }
}
