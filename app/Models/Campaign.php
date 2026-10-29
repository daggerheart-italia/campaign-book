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

    protected function casts(): array
    {
        return [
            'sync_version' => 'integer',
            'sync_generation' => 'integer',
        ];
    }

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

    /**
     * Render Markdown to safe HTML, resolving [[Page title]] and
     * [[Page title|label]] into links to this campaign's wiki pages.
     */
    public function renderMarkdown(?string $markdown): string
    {
        $html = Str::markdown($markdown ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $pagesByTitle = $this->wikiPages->keyBy(fn (WikiPage $page) => mb_strtolower(trim($page->title)));

        $segments = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $skipDepth = 0;

        foreach ($segments as $index => $segment) {
            if (str_starts_with($segment, '<')) {
                if (preg_match('/^<(\/?)(code|pre|a)\b/i', $segment, $tag)) {
                    $skipDepth = max(0, $skipDepth + ($tag[1] === '/' ? -1 : 1));
                }

                continue;
            }

            if ($skipDepth > 0) {
                continue;
            }

            $segments[$index] = preg_replace_callback(
                '/\[\[([^\[\]|]+?)(?:\|([^\[\]]+?))?\]\]/',
                function (array $match) use ($pagesByTitle): string {
                    $typedTitle = trim($match[1]);
                    $label = isset($match[2]) && trim($match[2]) !== '' ? trim($match[2]) : $typedTitle;
                    $page = $pagesByTitle->get(mb_strtolower(html_entity_decode($typedTitle, ENT_QUOTES)));

                    if ($page === null) {
                        return '<span class="wikilink-missing" title="Pagina non trovata">'.$label.'</span>';
                    }

                    return '<a href="'.e(route('wiki.show', [$this, $page])).'" class="wikilink">'.$label.'</a>';
                },
                $segment
            );
        }

        return implode('', $segments);
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
        if (! $user) {
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
        if (! $user) {
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
        if (! $user) {
            return null;
        }

        if ($this->owner_id === $user->id) {
            return 'gm';
        }

        $pivot = $this->users()->where('user_id', $user->id)->first();

        return $pivot ? $pivot->pivot->role : null;
    }
}
