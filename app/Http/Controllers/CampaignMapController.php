<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CampaignMapController extends Controller
{
    public const MAX_IMAGE_BYTES = 50 * 1024 * 1024;

    public const MAX_CHUNK_BYTES = 1024 * 1024;

    /**
     * Receive a single chunk of a map image being uploaded.
     */
    public function storeChunk(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $maxChunks = (int) ceil(self::MAX_IMAGE_BYTES / self::MAX_CHUNK_BYTES);

        $validated = $request->validate([
            'upload_id' => 'required|uuid',
            'index' => 'required|integer|min:0|lt:'.$maxChunks,
            'chunk' => 'required|file|max:'.(int) (self::MAX_CHUNK_BYTES / 1024),
        ]);

        Storage::disk('local')->putFileAs(
            $this->chunkDirectory($campaign, $validated['upload_id']),
            $request->file('chunk'),
            (string) $validated['index'],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Assemble the uploaded chunks and store the new custom campaign map image.
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'upload_id' => 'required|uuid',
            'total_chunks' => 'required|integer|min:1|max:'.(int) ceil(self::MAX_IMAGE_BYTES / self::MAX_CHUNK_BYTES),
        ]);

        $directory = $this->chunkDirectory($campaign, $validated['upload_id']);
        $disk = Storage::disk('local');
        $assembledPath = $directory.'/assembled';

        try {
            $this->assembleChunks($directory, (int) $validated['total_chunks']);

            $file = new UploadedFile($disk->path($assembledPath), 'map', null, null, true);

            if ($file->getSize() > self::MAX_IMAGE_BYTES) {
                throw ValidationException::withMessages([
                    'image' => 'L\'immagine supera la dimensione massima consentita.',
                ]);
            }

            Validator::make(['image' => $file], [
                'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif',
            ])->validate();

            $path = Storage::disk('public')->putFile('maps', $file);

            $dimensions = @getimagesize($file->getRealPath());
        } finally {
            $disk->deleteDirectory($directory);
        }

        // If this is the first map, mark it as default
        $isFirst = ! $campaign->maps()->exists();

        $map = $campaign->maps()->create([
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'image_path' => $path,
            'width' => $dimensions ? $dimensions[0] : null,
            'height' => $dimensions ? $dimensions[1] : null,
            'is_default' => $isFirst,
        ]);

        $message = "Mappa \"{$map->title}\" caricata con successo!";

        if ($request->wantsJson()) {
            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'message' => $message,
                'map' => $map,
            ]);
        }

        return back()->with('success', $message);
    }

    private function chunkDirectory(Campaign $campaign, string $uploadId): string
    {
        return 'map-chunks/'.$campaign->id.'/'.Auth::id().'/'.$uploadId;
    }

    private function assembleChunks(string $directory, int $totalChunks): void
    {
        $disk = Storage::disk('local');
        $output = fopen($disk->path($directory.'/assembled'), 'wb');

        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $chunkPath = $directory.'/'.$index;

                if (! $disk->exists($chunkPath)) {
                    throw ValidationException::withMessages([
                        'image' => 'Caricamento incompleto: mancano dei frammenti del file.',
                    ]);
                }

                $input = fopen($disk->path($chunkPath), 'rb');
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        } finally {
            fclose($output);
        }
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

        $map->pins()->delete();
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
