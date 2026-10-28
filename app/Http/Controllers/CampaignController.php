<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\SessionNote;
use App\Models\WikiPage;
use App\Models\WikiPageRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * Display a listing of the user's campaigns.
     */
    public function index()
    {
        $user = Auth::user();

        $campaigns = Campaign::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->with(['owner', 'users'])
            ->withCount(['wikiPages', 'mapPins', 'sessionNotes', 'maps'])
            ->distinct()
            ->latest()
            ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create()
    {
        return view('campaigns.create');
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'system' => 'nullable|string|max:100',
        ]);

        $campaign = Campaign::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'system' => $validated['system'] ?? 'Daggerheart',
            'owner_id' => Auth::id(),
        ]);

        // Attach creator as GM
        $campaign->users()->syncWithoutDetaching([
            Auth::id() => ['role' => 'gm'],
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campagna creata con successo! Sei il Game Master.');
    }

    /**
     * Display the specified campaign workspace with tabs.
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'owner',
            'users',
            'maps' => fn ($q) => $q->withCount('pins')->latest(),
            'wikiPages' => fn ($q) => $q->latest(),
            'mapPins' => fn ($q) => $q->where(fn ($pins) => $pins->whereNull('campaign_map_id')->orWhereHas('campaignMap'))->with(['wikiPage', 'campaignMap'])->latest(),
            'sessionNotes' => fn ($q) => $q->with('user')->orderBy('is_pinned', 'desc')->latest(),
        ]);

        // Determine active map: default map or first uploaded map
        $activeMap = $campaign->maps->firstWhere('is_default', true) ?? $campaign->maps->first();

        // If user is GM, also load deleted items for trash tab
        $isGm = $campaign->isGm(Auth::user());
        $trashedWikiPages = $isGm ? WikiPage::onlyTrashed()->where('campaign_id', $campaign->id)->get() : collect();
        $trashedMapPins = $isGm ? MapPin::onlyTrashed()->where('campaign_id', $campaign->id)->get() : collect();
        $trashedSessionNotes = $isGm ? SessionNote::onlyTrashed()->where('campaign_id', $campaign->id)->get() : collect();
        $trashedMaps = $isGm ? CampaignMap::onlyTrashed()->where('campaign_id', $campaign->id)->get() : collect();

        $wikiPages = $campaign->wikiPages
            ->sortBy(fn (WikiPage $page): string => mb_strtolower(($page->category ?: 'Generale')."\0".$page->title))
            ->values();
        $wikiPageTree = $this->buildWikiPageTree($wikiPages);
        $wikiPageItems = $wikiPages->map(fn (WikiPage $page): array => [
            'id' => $page->id,
            'title' => $page->title,
            'category' => $page->category ?: 'Generale',
            'excerpt' => Str::limit(strip_tags($page->content), 120),
            'updated_at' => $page->updated_at->diffForHumans(),
            'url' => route('wiki.show', [$campaign, $page]),
            'edit_url' => route('wiki.edit', [$campaign, $page]),
            'revisions_url' => route('wiki.revisions', [$campaign, $page]),
        ])->all();

        $categories = $wikiPages->pluck('category')
            ->unique()
            ->filter()
            ->sortBy(fn (string $category): string => mb_strtolower($category))
            ->values();

        return view('campaigns.show', compact(
            'campaign',
            'activeMap',
            'isGm',
            'trashedWikiPages',
            'trashedMapPins',
            'trashedSessionNotes',
            'trashedMaps',
            'categories',
            'wikiPages',
            'wikiPageItems',
            'wikiPageTree'
        ));
    }

    private function buildWikiPageTree(Collection $pages): array
    {
        $pagesById = $pages->keyBy('id');
        $childrenByParent = $pages
            ->filter(fn (WikiPage $page): bool => $page->parent_id !== null && $pagesById->has($page->parent_id))
            ->groupBy('parent_id');
        $rootPages = $pages->filter(
            fn (WikiPage $page): bool => $page->parent_id === null || ! $pagesById->has($page->parent_id)
        );

        $buildTree = function (Collection $siblings) use (&$buildTree, $childrenByParent): array {
            return $siblings->map(fn (WikiPage $page): array => [
                'page_id' => $page->id,
                'children' => $buildTree($childrenByParent->get($page->id, collect())),
            ])->all();
        };

        return $buildTree($rootPages);
    }

    /**
     * Join campaign via invite code form.
     */
    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->code);

        return $this->processJoin($code);
    }

    /**
     * Join campaign via direct invite link.
     */
    public function joinByCode(string $code)
    {
        return $this->processJoin($code);
    }

    protected function processJoin(string $code)
    {
        $campaign = Campaign::where('invite_code_player', $code)
            ->orWhere('invite_code_gm', $code)
            ->first();

        if (! $campaign) {
            return redirect()->route('campaigns.index')
                ->with('error', 'Codice di invito non valido o campagna non trovata.');
        }

        $user = Auth::user();
        $role = ($code === $campaign->invite_code_gm) ? 'gm' : 'player';

        $campaign->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);

        $roleLabel = ($role === 'gm') ? 'Game Master' : 'Giocatore';

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "Ti sei unito a \"{$campaign->name}\" con il ruolo di {$roleLabel}!");
    }

    /**
     * Update member role (GM only).
     */
    public function updateMemberRole(Request $request, Campaign $campaign, $userId)
    {
        $this->authorize('manageMembers', $campaign);

        $request->validate([
            'role' => 'required|in:gm,player',
        ]);

        if ((int) $userId === (int) $campaign->owner_id) {
            return back()->with('error', 'Il proprietario della campagna non può cambiare ruolo.');
        }

        $campaign->users()->updateExistingPivot($userId, [
            'role' => $request->role,
        ]);

        return back()->with('success', 'Ruolo del membro aggiornato.');
    }

    /**
     * Remove member from campaign (GM only).
     */
    public function removeMember(Campaign $campaign, $userId)
    {
        $this->authorize('manageMembers', $campaign);

        if ((int) $userId === (int) $campaign->owner_id) {
            return back()->with('error', 'Non puoi rimuovere il proprietario della campagna.');
        }

        $campaign->users()->detach($userId);

        return back()->with('success', 'Membro rimosso dalla campagna.');
    }

    /**
     * Empty trash (GM only).
     */
    public function emptyTrash(Campaign $campaign)
    {
        $this->authorize('manageTrash', $campaign);

        WikiPage::onlyTrashed()->where('campaign_id', $campaign->id)->forceDelete();
        MapPin::onlyTrashed()->where('campaign_id', $campaign->id)->forceDelete();
        SessionNote::onlyTrashed()->where('campaign_id', $campaign->id)->forceDelete();
        CampaignMap::onlyTrashed()->where('campaign_id', $campaign->id)->forceDelete();

        return back()->with('success', 'Cestino svuotato definitivamente.');
    }

    /**
     * Compact revisions (GM only): keeps only the 2 most recent revisions per wiki page.
     */
    public function compactRevisions(Campaign $campaign)
    {
        $this->authorize('manageTrash', $campaign);

        $wikiPageIds = $campaign->wikiPages()->withTrashed()->pluck('id');

        $deletedCount = 0;
        foreach ($wikiPageIds as $pageId) {
            $excessRevisions = WikiPageRevision::where('wiki_page_id', $pageId)
                ->orderBy('created_at', 'desc')
                ->skip(2)
                ->take(100)
                ->get();

            foreach ($excessRevisions as $rev) {
                $rev->delete();
                $deletedCount++;
            }
        }

        return back()->with('success', "Revisioni compattate con successo. Eliminate {$deletedCount} revisioni storiche ridondanti.");
    }

    /**
     * Export entire campaign content as Markdown.
     */
    public function export(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['wikiPages', 'sessionNotes', 'mapPins.campaignMap']);

        $md = '# '.$campaign->name."\n\n";
        $md .= '**Sistema:** '.$campaign->system."\n";
        $md .= '**Data Esportazione:** '.now()->format('d/m/Y H:i')."\n\n";

        if ($campaign->description) {
            $md .= "## Descrizione della Campagna\n\n";
            $md .= $campaign->description."\n\n---\n\n";
        }

        $md .= "# Wiki & Grimorio\n\n";
        $grouped = $campaign->wikiPages->groupBy('category');
        foreach ($grouped as $cat => $pages) {
            $md .= '## Categoria: '.$cat."\n\n";
            foreach ($pages as $page) {
                $md .= '### '.$page->title."\n\n";
                $md .= $page->content."\n\n";
                $md .= "---\n\n";
            }
        }

        $md .= "# Mappa & Punti d'Interesse Segnati\n\n";
        foreach ($campaign->mapPins as $pin) {
            $mapName = $pin->campaignMap ? " [Mappa: {$pin->campaignMap->title}]" : '';
            $md .= '- **'.$pin->title.'**'.$mapName." (X: {$pin->x}%, Y: {$pin->y}%)";
            if ($pin->description) {
                $md .= ' - '.$pin->description;
            }
            if ($pin->wikiPage) {
                $md .= " [Vedi Wiki: {$pin->wikiPage->title}]";
            }
            $md .= "\n";
        }
        $md .= "\n---\n\n";

        $md .= "# Diario di Sessione\n\n";
        foreach ($campaign->sessionNotes as $note) {
            $date = $note->session_date ? $note->session_date->format('d/m/Y') : $note->created_at->format('d/m/Y');
            $md .= '### '.$note->title." ({$date})\n\n";
            $md .= $note->content."\n\n";
        }

        $filename = Str::slug($campaign->name).'-export.md';

        return Response::make($md, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
