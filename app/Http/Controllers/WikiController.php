<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\WikiPage;
use App\Models\WikiPageRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WikiController extends Controller
{
    /**
     * Show form to create a new wiki page (supports prefill from map pin).
     */
    public function create(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $defaultCategories = ['Personaggi & PNG', 'Luoghi & Città', 'Fazioni & Gilde', 'Oggetti & Reliquie', 'Lore & Storia', 'Mostri & Minacce', 'Altro'];
        $parentPages = $this->availableParentPages($campaign);

        $pin = null;
        if ($request->filled('pin_id')) {
            $pin = $campaign->mapPins()->find($request->pin_id);
        }

        $prefillTitle = $request->query('title', $pin ? $pin->title : '');
        $prefillCategory = $request->query('category', $pin ? 'Luoghi & Città' : 'Generale');
        $prefillContent = $pin && $pin->description ? "## Note sul Luogo\n".$pin->description."\n" : '';
        $pinId = $pin ? $pin->id : null;

        return view('wiki.create', compact(
            'campaign',
            'defaultCategories',
            'parentPages',
            'pin',
            'prefillTitle',
            'prefillCategory',
            'prefillContent',
            'pinId'
        ));
    }

    /**
     * Store a new wiki page (and link to pin if pin_id is provided).
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'parent_id' => ['nullable', 'integer', Rule::in($this->availableParentPages($campaign)->modelKeys())],
            'content' => 'required|string',
            'pin_id' => 'nullable|exists:map_pins,id',
        ]);

        $wiki = $campaign->wikiPages()->create([
            'title' => $data['title'],
            'category' => $data['category'] ?: 'Generale',
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Create initial revision
        WikiPageRevision::create([
            'wiki_page_id' => $wiki->id,
            'user_id' => Auth::id(),
            'title' => $wiki->title,
            'category' => $wiki->category,
            'content' => $wiki->content,
            'summary' => 'Creazione iniziale',
        ]);

        // If created from a map pin, link the pin to this wiki page!
        if (! empty($data['pin_id'])) {
            $pin = $campaign->mapPins()->find($data['pin_id']);
            if ($pin) {
                $pin->update(['wiki_page_id' => $wiki->id]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pagina Wiki creata e collegata al segnaposto!',
                'wiki' => $wiki,
                'url' => route('wiki.show', [$campaign, $wiki]),
            ]);
        }

        return redirect()->route('wiki.show', [$campaign, $wiki])
            ->with('success', 'Pagina Wiki creata con successo!');
    }

    /**
     * Show a wiki page.
     */
    public function show(Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('view', $campaign);

        $wiki->load([
            'creator',
            'editor',
            'revisions.user',
            'mapPins.campaignMap',
            'parent' => fn ($query) => $query->where('campaign_id', $campaign->id),
            'children' => fn ($query) => $query->where('campaign_id', $campaign->id),
        ]);
        $childPages = $wiki->children
            ->sortBy(fn (WikiPage $page): string => mb_strtolower(($page->category ?: 'Generale')."\0".$page->title))
            ->values();
        $isGm = $campaign->isGm(Auth::user());

        return view('wiki.show', compact('campaign', 'wiki', 'childPages', 'isGm'));
    }

    /**
     * Show edit form.
     */
    public function edit(Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('update', $campaign);

        $defaultCategories = ['Personaggi & PNG', 'Luoghi & Città', 'Fazioni & Gilde', 'Oggetti & Reliquie', 'Lore & Storia', 'Mostri & Minacce', 'Altro'];
        $parentPages = $this->availableParentPages($campaign, $wiki);

        return view('wiki.edit', compact('campaign', 'wiki', 'defaultCategories', 'parentPages'));
    }

    /**
     * Update a wiki page and record revision.
     */
    public function update(Request $request, Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'parent_id' => ['nullable', 'integer', Rule::in($this->availableParentPages($campaign, $wiki)->modelKeys())],
            'content' => 'required|string',
            'summary' => 'nullable|string|max:255',
        ]);

        $wiki->update([
            'title' => $data['title'],
            'category' => $data['category'] ?: 'Generale',
            'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $wiki->parent_id,
            'content' => $data['content'],
            'updated_by' => Auth::id(),
        ]);

        // Record new revision
        WikiPageRevision::create([
            'wiki_page_id' => $wiki->id,
            'user_id' => Auth::id(),
            'title' => $wiki->title,
            'category' => $wiki->category,
            'content' => $wiki->content,
            'summary' => $data['summary'] ?: 'Modifica contenuto',
        ]);

        return redirect()->route('wiki.show', [$campaign, $wiki])
            ->with('success', 'Pagina Wiki aggiornata con una nuova revisione!');
    }

    /**
     * Soft delete wiki page (moves to trash).
     */
    public function destroy(Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('update', $campaign);

        $wiki->delete();

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Pagina spostata nel cestino.');
    }

    /**
     * Restore from trash (GM only).
     */
    public function restore(Campaign $campaign, $wikiId)
    {
        $this->authorize('restore', $campaign);

        $wiki = WikiPage::onlyTrashed()->where('campaign_id', $campaign->id)->findOrFail($wikiId);
        $wiki->restore();

        return back()->with('success', 'Pagina ripristinata con successo.');
    }

    /**
     * View revisions history.
     */
    public function revisions(Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('view', $campaign);

        $wiki->load(['revisions.user']);

        return view('wiki.revisions', compact('campaign', 'wiki'));
    }

    /**
     * Rollback to a specific revision.
     */
    public function restoreRevision(Request $request, Campaign $campaign, WikiPage $wiki, WikiPageRevision $revision)
    {
        $this->authorize('update', $campaign);

        $wiki->update([
            'title' => $revision->title,
            'category' => $revision->category,
            'content' => $revision->content,
            'updated_by' => Auth::id(),
        ]);

        // Create a new revision noting the rollback
        WikiPageRevision::create([
            'wiki_page_id' => $wiki->id,
            'user_id' => Auth::id(),
            'title' => $wiki->title,
            'category' => $wiki->category,
            'content' => $wiki->content,
            'summary' => 'Ripristino alla revisione del '.$revision->created_at->format('d/m/Y H:i'),
        ]);

        return redirect()->route('wiki.show', [$campaign, $wiki])
            ->with('success', 'Pagina ripristinata alla revisione selezionata.');
    }

    private function availableParentPages(Campaign $campaign, ?WikiPage $wiki = null): Collection
    {
        $pages = $campaign->wikiPages()
            ->get()
            ->sortBy(fn (WikiPage $page): string => mb_strtolower(($page->category ?: 'Generale')."\0".$page->title))
            ->values();

        if ($wiki === null) {
            return $pages;
        }

        $pagesById = $pages->keyBy('id');

        return $pages->reject(function (WikiPage $candidate) use ($pagesById, $wiki): bool {
            if ($candidate->is($wiki)) {
                return true;
            }

            $ancestorId = $candidate->parent_id;

            while ($ancestorId !== null) {
                if ((int) $ancestorId === (int) $wiki->id) {
                    return true;
                }

                $ancestorId = $pagesById->get($ancestorId)?->parent_id;
            }

            return false;
        })->values();
    }
}
