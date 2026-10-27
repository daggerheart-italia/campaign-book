<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SessionNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionNoteController extends Controller
{
    /**
     * Store a new session note.
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'session_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
        ]);

        $campaign->sessionNotes()->create([
            'user_id' => Auth::id(),
            'title' => $validated['title'] ?: ('Sessione del ' . ($validated['session_date'] ?? now()->format('d/m/Y'))),
            'content' => $validated['content'],
            'session_date' => $validated['session_date'] ?? now(),
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        return back()->with('success', 'Nota di sessione salvata!');
    }

    /**
     * Update session note.
     */
    public function update(Request $request, Campaign $campaign, SessionNote $note)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'session_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'session_date' => $validated['session_date'],
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        return back()->with('success', 'Nota aggiornata.');
    }

    /**
     * Toggle pinned status.
     */
    public function togglePin(Campaign $campaign, SessionNote $note)
    {
        $this->authorize('update', $campaign);

        $note->update([
            'is_pinned' => !$note->is_pinned,
        ]);

        return back()->with('success', $note->is_pinned ? 'Nota fissata in alto.' : 'Nota non più fissata.');
    }

    /**
     * Delete session note (move to trash).
     */
    public function destroy(Campaign $campaign, SessionNote $note)
    {
        $this->authorize('update', $campaign);

        $note->delete();

        return back()->with('success', 'Nota di sessione spostata nel cestino.');
    }

    /**
     * Restore session note from trash (GM only).
     */
    public function restore(Campaign $campaign, $noteId)
    {
        $this->authorize('restore', $campaign);

        $note = SessionNote::onlyTrashed()->where('campaign_id', $campaign->id)->findOrFail($noteId);
        $note->restore();

        return back()->with('success', 'Nota ripristinata dal cestino.');
    }
}
