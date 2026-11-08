<div x-data="{
    search: '',
    selectedCategory: 'all',
    pages: {{ Js::from($wikiItems) }},
    hasActiveFilter() {
        return this.search.trim() !== '' || this.selectedCategory !== 'all';
    },
    get visiblePages() {
        const term = this.search.trim().toLocaleLowerCase();
        if (!this.hasActiveFilter()) {
            return this.pages.filter(page => page.is_root);
        }

        return this.pages.filter(page =>
            (!term || page.title.toLocaleLowerCase().includes(term) || page.excerpt.toLocaleLowerCase().includes(term))
            && (this.selectedCategory === 'all' || page.category === this.selectedCategory)
        );
    }
}" class="space-y-4">
    <div class="glass-panel p-4 rounded-xl">
        <div class="relative">
            <x-app-icon name="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="text" x-model="search" @keydown.escape="search = ''" placeholder="Cerca tra le pagine della wiki..." class="input-premium !pl-10 text-xs sm:text-sm py-2">
            <button x-show="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs">
                <x-icon-label icon="x" label="Cancella ricerca" />
            </button>
        </div>
    </div>

    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <button @click="selectedCategory = 'all'"
                :class="selectedCategory === 'all' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                class="px-3 py-1 rounded-full text-xs font-semibold border transition-all shrink-0">
            Tutte (<span x-text="pages.length"></span>)
        </button>
        @foreach($categories as $category)
            <button @click="selectedCategory = {{ Js::from($category) }}"
                    :class="selectedCategory === {{ Js::from($category) }} ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                    class="px-3 py-1 rounded-full text-xs font-semibold border transition-all shrink-0">
                {{ $category }} (<span x-text="pages.filter(page => page.category === {{ Js::from($category) }}).length"></span>)
            </button>
        @endforeach
    </div>

    <p x-show="!hasActiveFilter() && pages.some(page => !page.is_root)" class="text-[11px] text-slate-500">
        Vengono mostrate le pagine principali: le sottopagine si trovano dentro ciascuna scheda, oppure usando la ricerca.
    </p>

    <div x-show="visiblePages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="page in visiblePages" :key="page.id">
            <article :class="{ 'wiki-card-stack': selectedCategory === 'all' && page.children_count > 0 }" class="glass-panel glass-panel-hover p-4 rounded-xl flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20" x-text="page.category"></span>
                        <span class="text-[10px] text-slate-400" x-text="page.updated_at"></span>
                    </div>
                    <a :href="page.url" class="mb-1.5 block font-['Cinzel'] text-base font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-1" x-text="page.title"></a>
                    <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-3" x-text="page.excerpt || 'Nessun contenuto...'"></p>
                </div>
                <div class="flex items-center justify-between gap-2 border-t border-white/[0.08] pt-3 text-xs">
                    <a :href="page.url" class="text-amber-400 hover:text-amber-300 font-semibold inline-flex min-h-11 items-center gap-1">
                        <span>Leggi</span>
                        <x-app-icon name="chevron-right" class="w-3 h-3" />
                    </a>
                    <span x-show="page.children_count > 0" class="text-slate-400" x-text="`Sottopagine (${page.children_count})`"></span>
                </div>
            </article>
        </template>
    </div>

    <div x-show="visiblePages.length === 0" class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
        <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna pagina trovata</h4>
        <p class="text-xs text-slate-400">Non ci sono pagine wiki corrispondenti ai filtri o alla ricerca corrente.</p>
    </div>
</div>
