<div class="relative isolate space-y-3" data-wiki-page-id="" x-bind:data-wiki-page-id="page ? page.id : ''">
    <article :class="{ 'wiki-card-stack': selectedCategory === 'all' && Array.isArray(page.children_ids) && page.children_ids.length > 0 }"
             class="glass-panel glass-panel-hover p-4 rounded-xl flex flex-col justify-between relative group">
        <div>
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20" x-text="page.category"></span>
                <span class="text-[10px] text-slate-400" x-text="formatUpdated(page.updated_at)"></span>
            </div>

            <a :href="page.url" class="mb-1.5 block font-['Cinzel'] text-base font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-1" x-text="page.title"></a>
            <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-3" x-text="page.excerpt || 'Nessun contenuto...'"></p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-white/[0.08] pt-3 text-xs">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <a :href="page.url" class="text-amber-400 hover:text-amber-300 font-semibold inline-flex min-h-11 items-center gap-1">
                    <x-icon-label icon="book-open" label="Leggi" />
                </a>
                <button x-show="Array.isArray(page.children_ids) && page.children_ids.length > 0" type="button" @click="openChildPages(page.id)" aria-haspopup="dialog"
                        class="inline-flex min-h-11 items-center gap-1 text-amber-300 hover:text-amber-200"
                        aria-label="Visualizza le sottopagine">
                    <x-icon-label icon="folder-kanban" label="Sottopagine" />
                    <span x-text="`(${page.children_ids.length})`"></span>
                    <x-app-icon name="chevron-right" class="w-3.5 h-3.5" />
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a :href="page.revisions_url" class="text-slate-400 hover:text-slate-200 text-[11px] flex min-h-11 items-center gap-1" title="Cronologia Revisioni">
                    <x-icon-label icon="clock-3" label="Revisioni" hideOnDesktop="true" />
                </a>
                <a :href="page.edit_url" class="btn-secondary !p-1.5 sm:!px-3 !rounded-lg text-slate-300 hover:text-amber-400">
                    <x-icon-label icon="pencil" label="Modifica" hideOnDesktop="true" />
                </a>
            </div>
        </div>
    </article>
</div>
