<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\WikiPage;
use Illuminate\Contracts\View\View;

class PublicCampaignController extends Controller
{
    public function show(string $slug): View
    {
        $campaign = $this->findCampaign($slug);

        $campaign->load([
            'maps' => fn ($query) => $query->orderByDesc('is_default')->latest(),
            'wikiPages' => fn ($query) => $query->orderBy('title'),
            'mapPins' => fn ($query) => $query->with('wikiPage')->latest(),
            'sessionNotes',
        ]);

        $wikiByCategory = $campaign->wikiPages
            ->groupBy(fn (WikiPage $page): string => $page->category ?: 'Generale')
            ->sortKeys();

        return view('public.campaign', compact('campaign', 'wikiByCategory'));
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
