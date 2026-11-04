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
        'public_slug',
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
    public function renderMarkdown(?string $markdown, bool $public = false): string
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
                function (array $match) use ($pagesByTitle, $public): string {
                    $typedTitle = trim($match[1]);
                    $label = isset($match[2]) && trim($match[2]) !== '' ? trim($match[2]) : $typedTitle;
                    $page = $pagesByTitle->get(mb_strtolower(html_entity_decode($typedTitle, ENT_QUOTES)));

                    if ($page === null) {
                        return '<span class="wikilink-missing" title="Pagina non trovata">'.$label.'</span>';
                    }

                    $url = $public
                        ? route('public.wiki', [$this->public_slug, $page])
                        : route('wiki.show', [$this, $page]);

                    return '<a href="'.e($url).'" class="wikilink">'.$label.'</a>';
                },
                $segment
            );
        }

        return implode('', $segments);
    }

    public function isPublic(): bool
    {
        return $this->public_slug !== null && $this->public_slug !== '';
    }

    public function mapPins(): HasMany
    {
        return $this->hasMany(MapPin::class);
    }

    /**
     * Generate a clean plain text excerpt from Markdown, resolving wikilink labels.
     */
    public static function excerpt(?string $markdown, int $limit = 120): string
    {
        if (empty($markdown)) {
            return '';
        }

        $text = $markdown;

        // Remove fenced code blocks
        $text = preg_replace('/```[\s\S]*?```/u', ' ', $text);

        // Remove inline code
        $text = preg_replace('/`([^`]+)`/u', '$1', $text);

        // Remove images ![alt](url)
        $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/u', ' ', $text);

        // Remove links [text](url) -> text
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $text);

        // Resolve wikilinks [[page]] or [[page|label]] -> label
        $text = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/u', '$1', $text);

        // Remove HTML tags
        $text = strip_tags($text);

        // Remove headings (# ## ### ...)
        $text = preg_replace('/^\s{0,3}#{1,6}\s+/mu', '', $text);

        // Remove blockquotes
        $text = preg_replace('/^\s{0,3}>\s?/mu', '', $text);

        // Remove horizontal rules
        $text = preg_replace('/^\s{0,3}([-*_])\s*(?:\1\s*){2,}$/mu', ' ', $text);

        // Remove list markers (- * +) and ordered lists (1. 2.)
        $text = preg_replace('/^\s{0,3}([-*+]|\d+\.)\s+/mu', '', $text);

        // Remove emphasis/bold/strikethrough (** __ * _ ~~)
        $text = preg_replace('/(\*\*|__)(.*?)\1/u', '$2', $text);
        $text = preg_replace('/(\*|_)(.*?)\1/u', '$2', $text);
        $text = preg_replace('/~~(.*?)~~/u', '$1', $text);

        // Collapse whitespace (newlines, tabs, multiple spaces)
        $text = preg_replace('/\s+/u', ' ', $text);

        return Str::limit(trim($text), $limit);
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

        if ($this->relationLoaded('users')) {
            $member = $this->users->firstWhere('id', $user->id);

            return $member !== null && $member->pivot?->role === 'gm';
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

        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', $user->id);
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

        if ($this->relationLoaded('users')) {
            $pivot = $this->users->firstWhere('id', $user->id);

            return $pivot ? $pivot->pivot?->role : null;
        }

        $pivot = $this->users()->where('user_id', $user->id)->first();

        return $pivot ? $pivot->pivot->role : null;
    }
}
