<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MapPin;
use App\Services\CampaignSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MapPinController extends Controller
{
    public function index(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);
        $validated = $request->validate([
            'map_id' => ['nullable', 'integer', Rule::exists('campaign_maps', 'id')->where('campaign_id', $campaign->id)],
        ]);

        $query = $campaign->mapPins()->with('wikiPage');
        if (isset($validated['map_id'])) {
            $query->where('campaign_map_id', $validated['map_id']);
        }

        return response()->json($query->get());
    }

    public function sync(Request $request, Campaign $campaign, CampaignSyncService $sync): JsonResponse
    {
        $this->authorize('view', $campaign);
        $validated = $request->validate([
            'cursor' => ['required', 'string', 'max:4096'],
            'map_id' => ['nullable', 'integer', Rule::exists('campaign_maps', 'id')->where('campaign_id', $campaign->id)],
        ]);

        return response()->json($sync->changes(
            $campaign,
            $validated['cursor'],
            'pin',
            isset($validated['map_id']) ? (int) $validated['map_id'] : null
        ));
    }

    public function snapshot(Request $request, Campaign $campaign, CampaignSyncService $sync): JsonResponse
    {
        $this->authorize('view', $campaign);
        $validated = $request->validate([
            'cursor' => ['nullable', 'string', 'max:4096'],
            'map_id' => ['nullable', 'integer', Rule::exists('campaign_maps', 'id')->where('campaign_id', $campaign->id)],
        ]);

        return response()->json($sync->snapshot(
            $campaign,
            'pin',
            $validated['cursor'] ?? null,
            isset($validated['map_id']) ? (int) $validated['map_id'] : null
        ));
    }

    public function store(Request $request, Campaign $campaign, CampaignSyncService $sync): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $campaign);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'x' => 'required|numeric|between:0,100',
            'y' => 'required|numeric|between:0,100',
            'campaign_map_id' => ['nullable', 'integer', Rule::exists('campaign_maps', 'id')->where('campaign_id', $campaign->id)],
            'description' => 'nullable|string',
            'wiki_page_id' => ['nullable', Rule::exists('wiki_pages', 'id')->where('campaign_id', $campaign->id)->whereNull('deleted_at')],
            'color' => 'nullable|string|max:20',
        ]);

        $pin = DB::transaction(function () use ($request, $campaign, $validated, $sync): MapPin {
            $pin = $campaign->mapPins()->create([
                'title' => $validated['title'],
                'campaign_map_id' => $validated['campaign_map_id'] ?? null,
                'x' => $validated['x'],
                'y' => $validated['y'],
                'description' => $validated['description'] ?? null,
                'wiki_page_id' => $validated['wiki_page_id'] ?? null,
                'color' => ($validated['color'] ?? null) ?: '#e11d48',
                'created_by' => $request->user()->id,
            ]);
            $sync->record($campaign, $pin, $request->user());

            return $pin->load('wikiPage');
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto d\'interesse aggiunto alla mappa!',
                'pin' => $pin,
            ]);
        }

        return back()->with('success', 'Punto d\'interesse aggiunto alla mappa!');
    }

    public function update(
        Request $request,
        Campaign $campaign,
        MapPin $pin,
        CampaignSyncService $sync
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $campaign);
        abort_unless($pin->campaign_id === $campaign->id, 404);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'x' => 'sometimes|required|numeric|between:0,100',
            'y' => 'sometimes|required|numeric|between:0,100',
            'campaign_map_id' => ['sometimes', 'nullable', 'integer', Rule::exists('campaign_maps', 'id')->where('campaign_id', $campaign->id)],
            'description' => 'sometimes|nullable|string',
            'wiki_page_id' => ['sometimes', 'nullable', Rule::exists('wiki_pages', 'id')->where('campaign_id', $campaign->id)->whereNull('deleted_at')],
            'color' => 'sometimes|nullable|string|max:20',
            'locked' => 'sometimes|boolean',
        ]);

        $isChangingPosition = $request->has('x')
            || $request->has('y')
            || (
                array_key_exists('campaign_map_id', $validated)
                && ($validated['campaign_map_id'] === null ? null : (int) $validated['campaign_map_id'])
                    !== ($pin->campaign_map_id === null ? null : (int) $pin->campaign_map_id)
            );
        if ($pin->locked && $isChangingPosition) {
            throw ValidationException::withMessages([
                'locked' => 'Sblocca il segnaposto prima di spostarlo.',
            ]);
        }

        $previousMapId = $pin->campaign_map_id;
        $pin = DB::transaction(function () use ($campaign, $pin, $validated, $request, $sync, $previousMapId): MapPin {
            $pin->update($validated);
            $sync->record($campaign, $pin, $request->user(), previousMapId: $previousMapId);

            return $pin->load('wikiPage');
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto d\'interesse aggiornato!',
                'pin' => $pin,
            ]);
        }

        return back()->with('success', 'Punto d\'interesse aggiornato!');
    }

    public function destroy(
        Request $request,
        Campaign $campaign,
        MapPin $pin,
        CampaignSyncService $sync
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $campaign);
        abort_unless($pin->campaign_id === $campaign->id, 404);

        DB::transaction(function () use ($request, $campaign, $pin, $sync): void {
            $previousMapId = $pin->campaign_map_id;
            $pin->delete();
            $sync->record($campaign, $pin, $request->user(), deleted: true, previousMapId: $previousMapId);
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pin rimosso dalla mappa.']);
        }

        return back()->with('success', 'Pin rimosso dalla mappa.');
    }

    public function restore(
        Request $request,
        Campaign $campaign,
        int $pinId,
        CampaignSyncService $sync
    ): RedirectResponse {
        $this->authorize('restore', $campaign);
        $pin = MapPin::onlyTrashed()->where('campaign_id', $campaign->id)->findOrFail($pinId);

        DB::transaction(function () use ($request, $campaign, $pin, $sync): void {
            $pin->restore();
            $sync->record($campaign, $pin, $request->user());
        });

        return back()->with('success', 'Pin ripristinato con successo.');
    }
}
