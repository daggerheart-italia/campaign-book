<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MapPin;
use App\Models\SessionNote;
use App\Models\WikiPage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PublicCampaignController extends Controller
{
    public function show(string $slug): View
    {
        $campaign = $this->findCampaign($slug);

        $campaign->load([
            'maps' => fn ($query) => $query->orderByDesc('is_default')->latest(),
            'wikiPages' => fn ($query) => $query->orderBy('title'),
            'mapPins' => fn ($query) => $query->whereHas('campaignMap')->with('wikiPage')->latest(),
            'sessionNotes',
        ]);

        $wikiPages = $campaign->wikiPages;
        $pageIds = $wikiPages->pluck('id');
        $childrenByParent = $wikiPages->groupBy('parent_id');

        $wikiItems = $wikiPages->map(fn (WikiPage $page): array => [
            'id' => $page->id,
            'is_root' => $page->parent_id === null || ! $pageIds->contains($page->parent_id),
            'children_count' => $childrenByParent->get($page->id, collect())->count(),
            'title' => $page->title,
            'category' => $page->category ?: 'Generale',
            'excerpt' => Str::limit(strip_tags($campaign->renderMarkdown($page->content, true)), 120),
            'updated_at' => $page->updated_at->diffForHumans(),
            'url' => route('public.wiki', [$campaign->public_slug, $page]),
        ])->values()->all();

        $categories = collect($wikiItems)->pluck('category')->unique()->sortBy(fn (string $category) => mb_strtolower($category))->values()->all();

        $maps = $campaign->maps->map(fn ($map): array => [
            'id' => $map->id,
            'title' => $map->title,
            'image_url' => $map->image_url,
            'is_default' => $map->is_default,
        ])->all();

        $pins = $campaign->mapPins->map(fn (MapPin $pin): array => [
            'id' => $pin->id,
            'campaign_map_id' => $pin->campaign_map_id,
            'title' => $pin->title,
            'description' => $pin->description,
            'x' => (float) $pin->x,
            'y' => (float) $pin->y,
            'color' => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $pin->color) ? $pin->color : '#e11d48',
            'wiki_page_title' => $pin->wikiPage?->title,
            'wiki_page_url' => $pin->wikiPage ? route('public.wiki', [$campaign->public_slug, $pin->wikiPage]) : null,
        ])->all();

        $notes = $campaign->sessionNotes->map(fn (SessionNote $note): array => [
            'id' => $note->id,
            'title' => $note->title,
            'date' => ($note->session_date ?? $note->created_at)->format('d/m/Y'),
            'is_pinned' => (bool) $note->is_pinned,
            'content_html' => $campaign->renderMarkdown($note->content, true),
            'plain' => Str::lower(strip_tags($note->title.' '.$note->content)),
        ])->all();

        return view('public.campaign', compact('campaign', 'wikiItems', 'categories', 'maps', 'pins', 'notes'));
    }

    public function wiki(string $slug, int $wiki): View
    {
        $campaign = $this->findCampaign($slug);
        $campaign->load('wikiPages');

        $page = $campaign->wikiPages->firstWhere('id', $wiki);
        abort_if($page === null, 404);

        $page->load('parent');
        $childPages = $campaign->wikiPages->where('parent_id', $page->id)->sortBy('title');

        return view('public.wiki', ['campaign' => $campaign, 'wiki' => $page, 'childPages' => $childPages]);
    }

    private function findCampaign(string $slug): Campaign
    {
        return Campaign::where('public_slug', $slug)->firstOrFail();
    }
}
