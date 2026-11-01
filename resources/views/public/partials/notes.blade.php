<div x-data="{
    search: '',
    notes: {{ Js::from($notes) }},
    openIds: [],
    get visibleNotes() {
        const term = this.search.trim().toLocaleLowerCase();

        return term ? this.notes.filter(note => note.plain.includes(term)) : this.notes;
    },
    toggle(id) {
        this.openIds = this.openIds.includes(id) ? this.openIds.filter(openId => openId !== id) : [...this.openIds, id];
    }
}" class="space-y-4">
    <div class="glass-panel p-4 rounded-xl relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-7 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="search" placeholder="Cerca tra le note di sessione..." class="input-premium !pl-10 text-xs sm:text-sm py-2">
    </div>

    <template x-for="note in visibleNotes" :key="note.id">
        <article class="glass-panel rounded-2xl overflow-hidden">
            <button type="button" @click="toggle(note.id)" :aria-expanded="openIds.includes(note.id)"
                    class="w-full flex items-center justify-between gap-3 p-4 sm:p-5 text-left">
                <div class="min-w-0">
                    <h3 class="font-['Cinzel'] text-sm sm:text-base font-bold text-white truncate">
                        <span x-show="note.is_pinned" title="In evidenza">📌</span>
                        <span x-text="note.title"></span>
                    </h3>
                    <p class="text-[11px] text-slate-400" x-text="note.date"></p>
                </div>
                <svg class="w-4 h-4 text-slate-300 shrink-0 transition-transform" :class="{ 'rotate-180': openIds.includes(note.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="openIds.includes(note.id)" x-cloak class="px-4 sm:px-5 pb-5 pt-4 border-t border-white/[0.08]">
                <div class="prose-dagger text-slate-200 text-sm leading-relaxed" x-html="note.content_html"></div>
            </div>
        </article>
    </template>

    <div x-show="visibleNotes.length === 0" class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
        <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna nota trovata</h4>
    </div>
</div>
