<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CampaignMapController extends Controller
{
    /**
     * Upload and store a new custom campaign map image.
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:15360', // max 15MB
        ]);

        $file = $request->file('image');
        $path = $file->store('maps', 'public');

        // Optional image dimensions
        $dimensions = @getimagesize($file->getRealPath());
        $width = $dimensions ? $dimensions[0] : null;
        $height = $dimensions ? $dimensions[1] : null;

        // If this is the first map, mark it as default
        $isFirst = !$campaign->maps()->exists();

        $map = $campaign->maps()->create([
            'created_by' => Auth::id(),
            'title' => $request->title,
            'image_path' => $path,
            'width' => $width,
            'height' => $height,
            'is_default' => $isFirst,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mappa caricata con successo!',
                'map' => $map,
            ]);
        }

        return back()->with('success', "Mappa \"{$map->title}\" caricata con successo!");
    }

    /**
     * Set a map as the default active map.
     */
    public function setDefault(Campaign $campaign, CampaignMap $map)
    {
        $this->authorize('update', $campaign);

        $campaign->maps()->update(['is_default' => false]);
        $map->update(['is_default' => true]);

        return back()->with('success', "La mappa \"{$map->title}\" è ora la mappa principale.");
    }

    /**
     * Delete a map image (soft delete).
     */
    public function destroy(Campaign $campaign, CampaignMap $map)
    {
        $this->authorize('update', $campaign);

        $map->delete();

        // If default was deleted, set next map as default
        if ($map->is_default) {
            $next = $campaign->maps()->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Mappa rimossa con successo.');
    }
}
