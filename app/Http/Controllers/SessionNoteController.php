<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SessionNote;
use App\Services\CampaignSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionNoteController extends Controller
{
    public function store(Request $request, Campaign $campaign, CampaignSyncService $sync): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'session_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $campaign, $validated, $sync): void {
            $note = $campaign->sessionNotes()->create([
                'user_id' => Auth::id(),
                'title' => ($validated['title'] ?? null) ?: ('Sessione del '.($validated['session_date'] ?? now()->format('d/m/Y'))),
                'content' => $validated['content'],
                'session_date' => $validated['session_date'] ?? now(),
                'is_pinned' => $request->boolean('is_pinned'),
            ]);
            $sync->record($campaign, $note, $request->user());
        });

        return back()->with('success', 'Nota di sessione salvata!');
    }

    public function update(
        Request $request,
        Campaign $campaign,
        SessionNote $note,
        CampaignSyncService $sync
    ): RedirectResponse {
        $this->authorize('update', $campaign);
        abort_unless($note->campaign_id === $campaign->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'session_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $campaign, $note, $validated, $sync): void {
            $note->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'session_date' => array_key_exists('session_date', $validated)
                    ? $validated['session_date']
                    : $note->session_date,
                'is_pinned' => $request->boolean('is_pinned'),
            ]);
            $sync->record($campaign, $note, $request->user());
        });

        return back()->with('success', 'Nota aggiornata.');
    }

    public function togglePin(
        Request $request,
        Campaign $campaign,
        SessionNote $note,
        CampaignSyncService $sync
    ): RedirectResponse {
        $this->authorize('update', $campaign);
        abort_unless($note->campaign_id === $campaign->id, 404);

        DB::transaction(function () use ($request, $campaign, $note, $sync): void {
            $note->update(['is_pinned' => ! $note->is_pinned]);
            $sync->record($campaign, $note, $request->user());
        });

        return back()->with('success', $note->is_pinned ? 'Nota fissata in alto.' : 'Nota non più fissata.');
    }

    public function destroy(
        Request $request,
        Campaign $campaign,
        SessionNote $note,
        CampaignSyncService $sync
    ): RedirectResponse|JsonResponse {
        $this->authorize('update', $campaign);
        abort_unless($note->campaign_id === $campaign->id, 404);

        DB::transaction(function () use ($request, $campaign, $note, $sync): void {
            $note->delete();
            $sync->record($campaign, $note, $request->user(), deleted: true);
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nota di sessione spostata nel cestino.']);
        }

        return back()->with('success', 'Nota di sessione spostata nel cestino.');
    }

    public function restore(
        Request $request,
        Campaign $campaign,
        int $noteId,
        CampaignSyncService $sync
    ): RedirectResponse {
        $this->authorize('restore', $campaign);
        $note = SessionNote::onlyTrashed()->where('campaign_id', $campaign->id)->findOrFail($noteId);

        DB::transaction(function () use ($request, $campaign, $note, $sync): void {
            $note->restore();
            $sync->record($campaign, $note, $request->user());
        });

        return back()->with('success', 'Nota ripristinata dal cestino.');
    }

    public function sync(Request $request, Campaign $campaign, CampaignSyncService $sync): JsonResponse
    {
        $this->authorize('view', $campaign);
        $validated = $request->validate([
            'cursor' => ['required', 'string', 'max:4096'],
        ]);

        return response()->json($sync->changes($campaign, $validated['cursor'], 'note'));
    }

    public function snapshot(Request $request, Campaign $campaign, CampaignSyncService $sync): JsonResponse
    {
        $this->authorize('view', $campaign);
        $validated = $request->validate([
            'cursor' => ['nullable', 'string', 'max:4096'],
        ]);

        return response()->json($sync->snapshot($campaign, 'note', $validated['cursor'] ?? null));
    }
}
