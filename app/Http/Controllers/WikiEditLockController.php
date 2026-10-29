<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\WikiPage;
use App\Services\WikiEditLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WikiEditLockController extends Controller
{
    public function __construct(private WikiEditLockService $locks) {}

    public function acquire(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $validated = $request->validate([
            'tab_id' => ['required', 'uuid'],
            'takeover' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->locks->acquire(
            $wiki,
            $request->user(),
            $validated['tab_id'],
            $validated['takeover'] ?? false
        ));
    }

    public function heartbeat(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $validated = $request->validate([
            'tab_id' => ['required', 'uuid'],
            'lock_token' => ['required', 'string', 'size:64'],
        ]);

        return response()->json($this->locks->heartbeat(
            $wiki,
            $request->user(),
            $validated['tab_id'],
            $validated['lock_token']
        ));
    }

    public function release(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $validated = $request->validate([
            'tab_id' => ['required', 'uuid'],
            'lock_token' => ['required', 'string', 'size:64'],
        ]);
        $this->locks->release($wiki, $request->user(), $validated['tab_id'], $validated['lock_token']);

        return response()->json(['released' => true]);
    }

    public function state(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiView($campaign, $wiki);
        $validated = $request->validate(['tab_id' => ['required', 'uuid']]);

        return response()->json($this->locks->state($wiki, $request->user(), $validated['tab_id']));
    }

    public function joinQueue(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $validated = $request->validate(['tab_id' => ['required', 'uuid']]);

        return response()->json($this->locks->joinQueue($wiki, $request->user(), $validated['tab_id']));
    }

    public function leaveQueue(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $this->locks->leaveQueue($wiki, $request->user());

        return response()->json(['left' => true]);
    }

    public function acceptOffer(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $validated = $request->validate(['tab_id' => ['required', 'uuid']]);

        return response()->json($this->locks->acceptOffer($wiki, $request->user(), $validated['tab_id']));
    }

    public function advance(Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $this->locks->advance($wiki);

        return response()->json(['advanced' => true]);
    }

    public function forceRelease(Request $request, Campaign $campaign, WikiPage $wiki): JsonResponse
    {
        $this->authorizeWikiUpdate($campaign, $wiki);
        $this->authorize('manageTrash', $campaign);
        $request->validate(['confirm' => ['required', 'accepted']]);

        $state = $this->locks->state($wiki, $request->user());
        $this->locks->forceRelease($wiki);
        Log::warning('Wiki edit lock force-released', [
            'campaign_id' => $campaign->id,
            'wiki_page_id' => $wiki->id,
            'forced_by' => $request->user()->id,
            'previous_owner_id' => $state['owner']['user_id'] ?? null,
        ]);

        return response()->json(['released' => true]);
    }

    private function authorizeWikiView(Campaign $campaign, WikiPage $wiki): void
    {
        abort_unless($wiki->campaign_id === $campaign->id, 404);
        $this->authorize('view', $campaign);
    }

    private function authorizeWikiUpdate(Campaign $campaign, WikiPage $wiki): void
    {
        abort_unless($wiki->campaign_id === $campaign->id, 404);
        $this->authorize('update', $campaign);
    }
}
