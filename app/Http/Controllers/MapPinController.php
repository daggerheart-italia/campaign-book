<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MapPin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MapPinController extends Controller
{
    /**
     * Get JSON list of map pins for the campaign (optionally filtered by map).
     */
    public function index(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $query = $campaign->mapPins()->with('wikiPage');

        if ($request->filled('map_id')) {
            $query->where('campaign_map_id', $request->map_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a new map pin placed on the custom image.
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'x' => 'required|numeric|between:0,100', // percentage from left
            'y' => 'required|numeric|between:0,100', // percentage from top
            'campaign_map_id' => 'nullable|exists:campaign_maps,id',
            'description' => 'nullable|string',
            'wiki_page_id' => 'nullable|exists:wiki_pages,id',
            'color' => 'nullable|string|max:20',
        ]);

        $pin = $campaign->mapPins()->create([
            'title' => $validated['title'],
            'campaign_map_id' => $validated['campaign_map_id'] ?? null,
            'x' => $validated['x'],
            'y' => $validated['y'],
            'description' => $validated['description'] ?? null,
            'wiki_page_id' => $validated['wiki_page_id'] ?? null,
            'color' => $validated['color'] ?: '#e11d48',
            'created_by' => Auth::id(),
        ]);

        $pin->load('wikiPage');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto d\'interesse aggiunto alla mappa!',
                'pin' => $pin,
            ]);
        }

        return back()->with('success', 'Punto d\'interesse aggiunto alla mappa!');
    }

    /**
     * Update pin coordinates or details (e.g. after dragging).
     */
    public function update(Request $request, Campaign $campaign, MapPin $pin)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'x' => 'sometimes|required|numeric|between:0,100',
            'y' => 'sometimes|required|numeric|between:0,100',
            'description' => 'nullable|string',
            'wiki_page_id' => 'nullable|exists:wiki_pages,id',
            'color' => 'nullable|string|max:20',
            'locked' => 'sometimes|boolean',
        ]);

        if ($pin->locked && ($request->has('x') || $request->has('y'))) {
            throw ValidationException::withMessages([
                'locked' => 'Sblocca il segnaposto prima di spostarlo.',
            ]);
        }

        $pin->update($validated);
        $pin->load('wikiPage');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto d\'interesse aggiornato!',
                'pin' => $pin,
            ]);
        }

        return back()->with('success', 'Punto d\'interesse aggiornato!');
    }

    /**
     * Delete pin (move to trash).
     */
    public function destroy(Request $request, Campaign $campaign, MapPin $pin)
    {
        $this->authorize('update', $campaign);

        $pin->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pin rimosso dalla mappa.',
            ]);
        }

        return back()->with('success', 'Pin rimosso dalla mappa.');
    }

    /**
     * Restore pin from trash (GM only).
     */
    public function restore(Campaign $campaign, $pinId)
    {
        $this->authorize('restore', $campaign);

        $pin = MapPin::onlyTrashed()->where('campaign_id', $campaign->id)->findOrFail($pinId);
        $pin->restore();

        return back()->with('success', 'Pin ripristinato con successo.');
    }
}
