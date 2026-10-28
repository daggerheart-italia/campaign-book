<div class="relative isolate space-y-3">
    <article :class="{ 'wiki-card-stack': page.children_ids.length > 0 }"
             class="glass-panel glass-panel-hover p-4 rounded-xl flex flex-col justify-between relative group">
        <div>
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20" x-text="page.category"></span>
                <span class="text-[10px] text-slate-400" x-text="page.updated_at"></span>
            </div>

            <a :href="page.url" class="mb-1.5 block font-['Cinzel'] text-base font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-1" x-text="page.title"></a>
            <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-3" x-text="page.excerpt || 'Nessun contenuto...'"></p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-white/[0.08] pt-3 text-xs">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <a :href="page.url" class="text-amber-400 hover:text-amber-300 font-semibold inline-flex min-h-11 items-center gap-1">
                    <span>Leggi</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <button x-show="page.children_ids.length > 0" type="button" @click="openChildPages(page.id)" aria-haspopup="dialog"
                        class="inline-flex min-h-11 items-center gap-1 text-amber-300 hover:text-amber-200"
                        aria-label="Visualizza le sottopagine">
                    <span>Sottopagine</span>
                    <span x-text="`(${page.children_ids.length})`"></span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a :href="page.revisions_url" class="text-slate-400 hover:text-slate-200 text-[11px] flex min-h-11 items-center gap-1" title="Cronologia Revisioni">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Revisioni</span>
                </a>
                <a :href="page.edit_url" class="btn-secondary !p-1.5 !rounded-lg text-slate-300 hover:text-amber-400" title="Modifica">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </a>
            </div>
        </div>
    </article>
</div>
