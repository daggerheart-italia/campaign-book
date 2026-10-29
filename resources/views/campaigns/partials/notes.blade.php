<div class="space-y-6"
     x-data="campaignNotesSync({{ Js::from([
        'campaignId' => $campaign->id,
        'syncUrl' => route('notes.sync', $campaign),
        'snapshotUrl' => route('notes.snapshot', $campaign),
        'cursor' => $notesSyncCursor,
        'initialNotes' => $initialNotes,
     ]) }})"
     x-init="init()">
    <div class="glass-panel p-4 sm:p-5 rounded-2xl">
        <div class="flex items-center justify-between cursor-pointer" @click="composerOpen = !composerOpen">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h3 class="font-['Cinzel'] text-sm sm:text-base font-bold text-white">Nuova Nota di Sessione</h3>
                    <p class="text-[11px] text-slate-400">Registra decisioni cruciali, bottini e rivelazioni della sessione.</p>
                </div>
            </div>
            <button type="button" class="btn-secondary !p-1.5 !rounded-lg text-slate-300">
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': composerOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <div x-show="composerOpen" x-transition class="mt-4 pt-4 border-t border-white/[0.08]" x-cloak>
            <form method="POST" action="{{ route('notes.store', $campaign) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Titolo Nota</label>
                        <input type="text" name="title" class="input-premium text-xs" placeholder="es. Sessione 4 - La Caverna dei Sussurri">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Data della Sessione</label>
                        <input type="date" name="session_date" value="{{ date('Y-m-d') }}" class="input-premium text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Contenuto *</label>
                    <x-markdown-editor :campaign="$campaign" min-height="8rem" placeholder="Cosa è accaduto? Digita [[ per collegare una pagina della wiki." />
                </div>
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4 rounded bg-slate-900 border-white/20 text-amber-500 focus:ring-amber-500/20">
                        <span class="text-xs text-slate-300">Fissa in evidenza in cima</span>
                    </label>
                    <button type="submit" class="btn-primary text-xs py-2 px-4"><span>Salva Nota</span></button>
                </div>
            </form>
        </div>
    </div>

    <p x-show="syncError" x-text="syncError" role="status" class="text-xs text-rose-300" x-cloak></p>
    <button x-show="resetRequired" type="button" class="btn-secondary text-xs py-2 px-3" @click="reloadSnapshot()" x-cloak>
        Ricarica la snapshot delle note
    </button>

    <template x-if="notes.length === 0">
        <div class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna nota registrata</h4>
            <p class="text-xs text-slate-400 mb-3">Il diario di sessione è ancora intonso. Inizia a scriverlo!</p>
            <button @click="composerOpen = true" class="btn-primary text-xs py-2 px-3"><span>Scrivi la prima Nota</span></button>
        </div>
    </template>
    <div class="space-y-4" x-show="notes.length > 0">
        <template x-for="note in notes" :key="note.id">
            <div class="glass-panel p-4 sm:p-5 rounded-2xl relative transition-all"
                 :class="note.is_pinned ? 'border-amber-500/40 shadow-lg shadow-amber-500/5' : ''">
                <div class="flex items-start justify-between gap-3 mb-2.5">
                    <div class="flex items-center gap-2">
                        <span x-show="note.is_pinned" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30" x-cloak>
                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/></svg>
                            Fissata
                        </span>
                        <h3 class="font-['Cinzel'] text-base sm:text-lg font-bold text-white" x-text="note.title"></h3>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <form method="POST" :action="`/campaigns/${campaignId}/notes/${note.id}/pin`" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-white/5" :title="note.is_pinned ? 'Rimuovi evidenza' : 'Fissa in alto'">
                                <svg class="w-4 h-4" :class="note.is_pinned ? 'text-amber-400 fill-amber-400/20' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            </button>
                        </form>
                        <button @click="editingNote = { ...note }; editModalOpen = true" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5" title="Modifica Nota">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <form method="POST" :action="`/campaigns/${campaignId}/notes/${note.id}`" onsubmit="return confirm('Spostare questa nota nel cestino?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10" title="Elimina">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-slate-400 mb-3 pb-2 border-b border-white/[0.05]">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="note.session_date_label"></span>
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7-7z"/></svg>
                        <span x-text="note.author_name"></span>
                    </span>
                </div>
                <div class="prose-dagger text-xs sm:text-sm" x-html="note.content_html"></div>
            </div>
        </template>
    </div>

    <div x-show="editModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-cloak>
        <div @click.away="editModalOpen = false" class="glass-panel p-6 rounded-2xl max-w-lg w-full relative">
            <button @click="editModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-4">Modifica Nota di Sessione</h3>
            <form :action="`/campaigns/${campaignId}/notes/${editingNote.id}`" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Titolo</label>
                    <input type="text" name="title" x-model="editingNote.title" required class="input-premium text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Data Sessione</label>
                    <input type="date" name="session_date" x-model="editingNote.session_date" class="input-premium text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Contenuto</label>
                    <x-markdown-editor :campaign="$campaign" min-height="10rem" x-model="editingNote.content" />
                </div>
                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" :checked="editingNote.is_pinned" class="w-4 h-4 rounded bg-slate-900 border-white/20 text-amber-500">
                        <span class="text-xs text-slate-300">Fissa in evidenza</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="editModalOpen = false" class="btn-secondary text-xs py-2 px-3">Annulla</button>
                        <button type="submit" class="btn-primary text-xs py-2 px-4">Aggiorna Nota</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function campaignNotesSync(config) {
    return {
        campaignId: config.campaignId,
        cursor: config.cursor,
        notes: config.initialNotes,
        composerOpen: false,
        editModalOpen: false,
        editingNote: {},
        syncError: '',
        resetRequired: false,
        syncing: false,
        resetting: false,
        init() {
            this.syncTimer = window.setInterval(() => this.syncChanges(), 8000);
        },
        destroy() {
            window.clearInterval(this.syncTimer);
        },
        async syncChanges() {
            if (this.syncing || this.resetting || this.resetRequired) return;
            this.syncing = true;
            try {
                let hasMore = true;
                let pagesProcessed = 0;
                while (hasMore && pagesProcessed < 5) {
                    pagesProcessed++;
                    const response = await fetch(`${config.syncUrl}?cursor=${encodeURIComponent(this.cursor)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || `Sincronizzazione non riuscita (${response.status}).`);
                    if (data.reset_required) {
                        this.resetRequired = true;
                        throw new Error('Le note sono state reimpostate. Carica una nuova snapshot per aggiornare la vista.');
                    }
                    for (const change of data.changes) this.applyChange(change);
                    this.cursor = data.next_cursor;
                    hasMore = data.has_more;
                }
                this.sortNotes();
                window.dispatchEvent(new CustomEvent('notes-count-updated', { detail: this.notes.length }));
                this.syncError = '';
            } catch (error) {
                this.syncError = error.message;
            } finally {
                this.syncing = false;
            }
        },
        async reloadSnapshot() {
            if (this.resetting) return;
            this.resetting = true;
            try {
                let cursor = null;
                let hasMore = true;
                let items = [];
                let deltaCursor = null;
                while (hasMore) {
                    const url = new URL(config.snapshotUrl, window.location.origin);
                    if (cursor) url.searchParams.set('cursor', cursor);
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || `Snapshot non riuscita (${response.status}).`);
                    if (data.reset_required) throw new Error('La campagna è stata reimpostata durante la snapshot. Riprova.');
                    items = items.concat(data.items);
                    cursor = data.next_cursor;
                    hasMore = data.has_more;
                    deltaCursor = data.delta_cursor;
                }
                this.notes = items;
                this.cursor = deltaCursor;
                this.resetRequired = false;
                this.syncError = '';
                this.sortNotes();
                window.dispatchEvent(new CustomEvent('notes-count-updated', { detail: this.notes.length }));
            } catch (error) {
                this.syncError = error.message;
                this.resetRequired = true;
            } finally {
                this.resetting = false;
            }
        },
        applyChange(change) {
            const index = this.notes.findIndex(note => note.id === change.id);
            if (change.deleted) {
                if (index !== -1) this.notes.splice(index, 1);
                return;
            }
            const note = {
                ...change,
                session_date_label: change.session_date_label,
                author_name: change.author_name || 'Autore sconosciuto',
            };
            if (index === -1) this.notes.push(note);
            else this.notes.splice(index, 1, { ...this.notes[index], ...note });
        },
        sortNotes() {
            this.notes.sort((left, right) => {
                if (left.is_pinned !== right.is_pinned) return left.is_pinned ? -1 : 1;
                return (right.created_at || '').localeCompare(left.created_at || '');
            });
        },
    };
}
</script>
