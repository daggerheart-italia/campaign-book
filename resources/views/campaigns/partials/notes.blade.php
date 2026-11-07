<div class="space-y-6"
x-data="{
            campaignId: {{ $campaign->id }},
            search: '',
            composerOpen: false,
            editModalOpen: false,
            editingNote: {},
            applyChange(change) {
                if (change.deleted) {
                    this.notes = this.notes.filter(n => n.id !== change.id);
                    if (this.editingNote.id === change.id) {
                        this.editModalOpen = false;
                    }
                    return;
                }
                const existingIndex = this.notes.findIndex(n => n.id === change.id);
                if (existingIndex !== -1) {
                    this.notes.splice(existingIndex, 1, change);
                } else {
                    this.notes.unshift(change);
                }
                this.sortNotes();
            },
            sortNotes() {
                this.notes.sort((a, b) => new Date(b.session_date || b.created_at) - new Date(a.session_date || a.created_at));
            },
            init() {
                try {
                    const raw = this.$el.getAttribute('data-initial-notes');
                    this.notes = raw ? JSON.parse(raw) : [];
                } catch (e) {
                    this.notes = [];
                }
                this.sortNotes();
                window.campaignSyncManager?.registerEntity('note', {
                 applyChange: (change) => this.applyChange(change),
                 onStatsUpdate: (stats) => {
                     if (stats && typeof stats.notes_count !== 'undefined') {
                         const root = this.$el.closest('[x-data]');
                         if (root && root._x_dataStack) {
                             root._x_dataStack[0].stats.notes_count = stats.notes_count;
                         } else {
                             window.dispatchEvent(new CustomEvent('campaign-stats-updated', { detail: stats }));
                         }
                     }
                 },
                 onReset: (items) => {
                     this.notes = items;
                     this.sortNotes();
                 },
             });
         },
         get visibleNotes() {
             const term = this.search.trim().toLocaleLowerCase();
             if (!term) return this.notes;
             return this.notes.filter(note => [note.title, note.content, note.author_name]
                 .some(value => (value || '').toLocaleLowerCase().includes(term)));
         },
      }"
      data-initial-notes='@json($initialNotes)'>
    <div class="glass-panel p-4 sm:p-5 rounded-2xl">
        <div class="flex items-center justify-between cursor-pointer" @click="composerOpen = !composerOpen">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <x-app-icon name="pencil" class="w-4 h-4" />
                </div>
                <div>
                    <h3 class="font-['Cinzel'] text-sm sm:text-base font-bold text-white">Nuova Nota di Sessione</h3>
                    <p class="text-[11px] text-slate-400">Registra decisioni cruciali, bottini e rivelazioni della sessione.</p>
                </div>
            </div>
            <button type="button" class="btn-secondary !p-1.5 !rounded-lg text-slate-300">
                <x-app-icon name="chevron-down" class="w-4 h-4 transition-transform duration-200" ::class="{ 'rotate-180': composerOpen }" />
            </button>
        </div>

        <div x-show="composerOpen" x-transition class="mt-4 pt-4 border-t border-white/[0.08]" x-cloak>
            <form method="POST" action="{{ route('notes.store', $campaign) }}" class="space-y-3" x-data="noteComposerAutosave({ campaignId: {{ $campaign->id }} })" x-init="init()" @submit="clearDraft()">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Titolo Nota</label>
                        <input type="text" name="title" class="input-premium text-xs" placeholder="es. Sessione 4 - La Caverna dei Sussurri" x-model="title" @input="scheduleSave()">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Data della Sessione</label>
                        <input type="date" name="session_date" value="{{ date('Y-m-d') }}" class="input-premium text-xs" x-model="sessionDate" @change="scheduleSave()">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Contenuto *</label>
                    <x-markdown-editor :campaign="$campaign" min-height="8rem" placeholder="Cosa è accaduto? Digita [[ per collegare una pagina della wiki." x-model="content" @input="scheduleSave()" />
                </div>
                <div class="flex items-center flex-wrap justify-between pt-1 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4 rounded bg-slate-900 border-white/20 text-amber-500 focus:ring-amber-500/20" x-model="isPinned" @change="scheduleSave()">
                            <span class="text-xs text-slate-300">Fissa in evidenza</span>
                        </label>
                        <span class="text-xs text-slate-400" x-text="autosaveStatus"></span>
                    
                    <button type="submit" class="btn-primary text-xs py-2 px-4"><x-icon-label icon="check" label="Salva Nota" /></button>
                </div>
            </form>
        </div>
    </div>

    <template x-if="notes.length === 0">
        <div class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-3">
                <x-app-icon name="file-text" class="w-6 h-6" />
            </div>
            <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna nota registrata</h4>
            <p class="text-xs text-slate-400 mb-3">Il diario di sessione è ancora intonso. Inizia a scriverlo!</p>
            <button @click="composerOpen = true" class="btn-primary text-xs py-2 px-3"><x-icon-label icon="plus" label="Scrivi la prima Nota" /></button>
        </div>
    </template>
    <div class="glass-panel p-4 rounded-xl relative" x-show="notes.length > 0">
        <x-app-icon name="search" class="w-4 h-4 text-slate-400 absolute left-7 top-1/2 -translate-y-1/2" />
        <input type="text" x-model="search" @keydown.escape="search = ''" placeholder="Cerca tra le note di sessione..."
               class="input-premium !pl-10 text-xs sm:text-sm py-2">
        <button type="button" x-show="search" @click="search = ''" class="absolute right-7 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs">
            <x-icon-label icon="x" label="Cancella ricerca" />
        </button>
    </div>
    <div x-show="notes.length > 0 && visibleNotes.length === 0" class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto border-dashed border-white/20" x-cloak>
        <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna nota trovata</h4>
        <p class="text-xs text-slate-400">Nessuna nota corrisponde alla ricerca.</p>
    </div>
    <div class="space-y-4" x-show="notes.length > 0">
        <template x-for="note in visibleNotes" :key="note.id">
            <div class="glass-panel p-4 sm:p-5 rounded-2xl relative transition-all"
                 :class="note.is_pinned ? 'border-amber-500/40 shadow-lg shadow-amber-500/5' : ''">
                <div class="flex items-start justify-between gap-3 mb-2.5">
                    <div class="flex items-center gap-2">
                        <span x-show="note.is_pinned" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30" x-cloak>
                            <x-app-icon name="bookmark" class="w-3 h-3 text-amber-400" />
                            Fissata
                        </span>
                        <h3 class="font-['Cinzel'] text-base sm:text-lg font-bold text-white" x-text="note.title"></h3>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <form method="POST" :action="`/campaigns/${campaignId}/notes/${note.id}/pin`" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-white/5">
                                <span class="inline-flex items-center gap-2">
                                    <x-app-icon name="bookmark" class="w-4 h-4" ::class="note.is_pinned ? 'text-amber-400 fill-amber-400/20' : ''" />
                                    <span class="icon-label-mobile" :data-tooltip="note.is_pinned ? 'Rimuovi evidenza' : 'Fissa in alto'" x-text="note.is_pinned ? 'Rimuovi evidenza' : 'Fissa in alto'"></span>
                                </span>
                            </button>
                        </form>
                        <button @click="editingNote = { ...note }; editModalOpen = true" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5">
                            <x-icon-label icon="pencil" label="Modifica Nota" />
                        </button>
                        <form method="POST" :action="`/campaigns/${campaignId}/notes/${note.id}`" onsubmit="return confirm('Spostare questa nota nel cestino?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10">
                                <x-icon-label icon="trash-2" label="Elimina" />
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-slate-400 mb-3 pb-2 border-b border-white/[0.05]">
                    <span class="flex items-center gap-1">
                        <x-app-icon name="calendar-days" class="w-3.5 h-3.5 text-slate-500" />
                        <span x-text="note.session_date_label"></span>
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <x-app-icon name="user-round" class="w-3.5 h-3.5 text-slate-500" />
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
                <x-app-icon name="x" class="w-5 h-5" />
            </button>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-4">Modifica Nota di Sessione</h3>
            <form :action="`/campaigns/${campaignId}/notes/${editingNote.id}`" method="POST" class="space-y-3" x-data="{ lastSaved: '' }" @submit="lastSaved = 'Salvataggio...'">
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
                        <span class="text-xs text-slate-400" x-text="lastSaved"></span>
                        <button type="button" @click="editModalOpen = false" class="btn-secondary text-xs py-2 px-3"><x-icon-label icon="x" label="Annulla" /></button>
                        <button type="submit" class="btn-primary text-xs py-2 px-4"><x-icon-label icon="check" label="Aggiorna Nota" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function noteComposerAutosave(config) {
    return {
        autosaveKey: `note-composer-autosave:${config.campaignId}`,
        title: '',
        sessionDate: new Date().toISOString().slice(0,10),
        content: '',
        isPinned: false,
        autosaveStatus: 'Nessun salvataggio automatico locale',
        timer: null,
        init() {
            this.restoreDraft();
            this.autosaveTimer = window.setInterval(() => this.saveDraft(), 15000);
        },
        destroy() {
            if (this.autosaveTimer) window.clearInterval(this.autosaveTimer);
            if (this.timer) window.clearTimeout(this.timer);
        },
        scheduleSave() {
            if (this.timer) window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => this.saveDraft(), 800);
        },
        saveDraft() {
            try {
                const payload = {
                    title: this.title,
                    session_date: this.sessionDate,
                    content: this.content,
                    is_pinned: this.isPinned,
                    saved_at: new Date().toISOString(),
                };
                localStorage.setItem(this.autosaveKey, JSON.stringify(payload));
                const time = new Date().toLocaleTimeString('it-IT');
                this.autosaveStatus = `Bozza salvata automaticamente alle ${time}`;
            } catch (e) {
                this.autosaveStatus = 'Salvataggio automatico locale non riuscito';
            }
        },
        restoreDraft() {
            try {
                const raw = localStorage.getItem(this.autosaveKey);
                if (!raw) return;
                const payload = JSON.parse(raw);
                if (!payload) return;
                if ((payload.title && payload.title.trim() !== '') || (payload.content && payload.content.trim() !== '')) {
                    if (window.confirm('È stata trovata una bozza locale non salvata per una nuova nota di sessione. Vuoi ripristinarla?')) {
                        this.title = payload.title || '';
                        this.sessionDate = payload.session_date || new Date().toISOString().slice(0,10);
                        this.content = payload.content || '';
                        this.isPinned = !!payload.is_pinned;
                        this.autosaveStatus = 'Bozza locale ripristinata';
                    }
                }
            } catch (e) {
            }
        },
        clearDraft() {
            try { localStorage.removeItem(this.autosaveKey); } catch (e) {}
        },
    };
}
</script>
