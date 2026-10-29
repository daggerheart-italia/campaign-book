<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\WikiPage;
use App\Models\WikiPageRevision;
use App\Services\CampaignSyncService;
use App\Services\WikiEditLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    public function store(Request $request, Campaign $campaign, CampaignSyncService $sync)
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'parent_id' => ['nullable', 'integer', Rule::in($this->availableParentPages($campaign)->modelKeys())],
            'content' => 'required|string',
            'pin_id' => [
                'nullable',
                'integer',
                Rule::exists('map_pins', 'id')->where('campaign_id', $campaign->id)->whereNull('deleted_at'),
            ],
        ]);

        $wiki = DB::transaction(function () use ($campaign, $data, $sync): WikiPage {
            $wiki = $campaign->wikiPages()->create([
                'title' => $data['title'],
                'category' => ($data['category'] ?? null) ?: 'Generale',
                'parent_id' => $data['parent_id'] ?? null,
                'content' => $data['content'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            WikiPageRevision::create([
                'wiki_page_id' => $wiki->id,
                'user_id' => Auth::id(),
                'title' => $wiki->title,
                'category' => $wiki->category,
                'content' => $wiki->content,
                'summary' => 'Creazione iniziale',
            ]);

            if (! empty($data['pin_id'])) {
                $pin = $campaign->mapPins()->findOrFail($data['pin_id']);
                $previousMapId = $pin->campaign_map_id;
                $pin->update(['wiki_page_id' => $wiki->id]);
                $sync->record($campaign, $pin, Auth::user(), previousMapId: $previousMapId);
            }

            return $wiki;
        });

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
        abort_unless($wiki->campaign_id === $campaign->id, 404);

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
    public function edit(Request $request, Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('update', $campaign);
        abort_unless($wiki->campaign_id === $campaign->id, 404);

        $defaultCategories = ['Personaggi & PNG', 'Luoghi & Città', 'Fazioni & Gilde', 'Oggetti & Reliquie', 'Lore & Storia', 'Mostri & Minacce', 'Altro'];
        $parentPages = $this->availableParentPages($campaign, $wiki);
        $restoreRevision = null;
        if ($request->filled('revision')) {
            $restoreRevision = $wiki->revisions()->whereKey($request->integer('revision'))->firstOrFail();
        }
        $categoryOptions = collect($defaultCategories)
            ->push($wiki->category, $restoreRevision?->category)
            ->filter()
            ->unique()
            ->values();

        return view('wiki.edit', compact('campaign', 'wiki', 'defaultCategories', 'categoryOptions', 'parentPages', 'restoreRevision'));
    }

    /**
     * Update a wiki page and record revision.
     */
    public function update(Request $request, Campaign $campaign, WikiPage $wiki, WikiEditLockService $locks)
    {
        $this->authorize('update', $campaign);
        abort_unless($wiki->campaign_id === $campaign->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'parent_id' => ['nullable', 'integer', Rule::in($this->availableParentPages($campaign, $wiki)->modelKeys())],
            'content' => 'required|string',
            'summary' => 'nullable|string|max:255',
            'tab_id' => ['required', 'uuid'],
            'lock_token' => ['required', 'string', 'size:64'],
            'restore_revision_id' => [
                'nullable',
                'integer',
                Rule::exists('wiki_page_revisions', 'id')->where('wiki_page_id', $wiki->id),
            ],
        ]);

        try {
            $locks->saveWithLock(
                $wiki,
                $request->user(),
                $data['tab_id'],
                $data['lock_token'],
                function () use ($wiki, $data): void {
                    DB::transaction(function () use ($wiki, $data): void {
                        $wiki->update([
                            'title' => $data['title'],
                            'category' => ($data['category'] ?? null) ?: 'Generale',
                            'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $wiki->parent_id,
                            'content' => $data['content'],
                            'updated_by' => Auth::id(),
                        ]);

                        WikiPageRevision::create([
                            'wiki_page_id' => $wiki->id,
                            'user_id' => Auth::id(),
                            'title' => $wiki->title,
                            'category' => $wiki->category,
                            'content' => $wiki->content,
                            'summary' => ($data['summary'] ?? null) ?: (
                                isset($data['restore_revision_id'])
                                    ? 'Ripristino revisione'
                                    : 'Modifica contenuto'
                            ),
                        ]);
                    });
                }
            );
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 409) {
                throw $exception;
            }

            return back()->withInput()->withErrors(['lock' => $exception->getMessage()]);
        }

        return redirect()->route('wiki.show', [$campaign, $wiki])
            ->with('success', 'Pagina Wiki aggiornata con una nuova revisione!');
    }

    /**
     * Soft delete wiki page (moves to trash).
     */
    public function destroy(Campaign $campaign, WikiPage $wiki)
    {
        $this->authorize('update', $campaign);
        abort_unless($wiki->campaign_id === $campaign->id, 404);

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
        abort_unless($wiki->campaign_id === $campaign->id, 404);

        $wiki->load(['revisions.user']);

        return view('wiki.revisions', compact('campaign', 'wiki'));
    }

    /**
     * Rollback to a specific revision.
     */
    public function restoreRevision(Campaign $campaign, WikiPage $wiki, WikiPageRevision $revision)
    {
        $this->authorize('update', $campaign);
        abort_unless($wiki->campaign_id === $campaign->id, 404);
        abort_unless($revision->wiki_page_id === $wiki->id, 404);

        return redirect()->route('wiki.edit', [
            'campaign' => $campaign,
            'wiki' => $wiki,
            'revision' => $revision->id,
        ]);
    }

    public function sync(Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorize('view', $campaign);
        abort_unless($wiki->campaign_id === $campaign->id, 404);
        $wiki->loadMissing('editor');

        return response()->json([
            'updated_at' => $wiki->updated_at?->toIso8601String(),
            'updated_by' => $wiki->editor
                ? ['id' => $wiki->editor->id, 'name' => $wiki->editor->name]
                : null,
        ]);
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
