<div x-data="{ 
    search: '', 
    selectedCategory: 'all',
    pages: {{ Js::from($campaign->wikiPages->map(fn($p) => [
        'id' => $p->id,
        'title' => $p->title,
        'category' => $p->category ?: 'Generale',
        'excerpt' => Str::limit(strip_tags($p->content), 120),
        'created_at' => $p->created_at->format('d/m/Y'),
        'updated_at' => $p->updated_at->diffForHumans(),
        'url' => route('wiki.show', [$campaign, $p]),
        'edit_url' => route('wiki.edit', [$campaign, $p]),
        'revisions_url' => route('wiki.revisions', [$campaign, $p]),
    ])) }},
    get filteredPages() {
        return this.pages.filter(page => {
            const matchesSearch = !this.search || page.title.toLowerCase().includes(this.search.toLowerCase()) || page.excerpt.toLowerCase().includes(this.search.toLowerCase());
            const matchesCategory = this.selectedCategory === 'all' || page.category === this.selectedCategory;
            return matchesSearch && matchesCategory;
        });
    }
}" class="space-y-4">

    <!-- Controls Bar -->
    <div class="glass-panel p-4 rounded-xl flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Cerca tra le pagine della wiki..." 
                   class="input-premium pl-9 text-xs sm:text-sm py-2">
            <button x-show="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <!-- Add Wiki Page CTA -->
        <a href="{{ route('wiki.create', $campaign) }}" class="btn-primary text-xs sm:text-sm py-2 px-4 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Nuova Pagina Wiki</span>
        </a>
    </div>

    <!-- Category Filter Chips -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <button @click="selectedCategory = 'all'" 
                :class="selectedCategory === 'all' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                class="px-3 py-1 rounded-full text-xs font-semibold border transition-all shrink-0">
            Tutte (<span x-text="pages.length"></span>)
        </button>

        @foreach($categories as $category)
            <button @click="selectedCategory = '{{ $category }}'" 
                    :class="selectedCategory === '{{ $category }}' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                    class="px-3 py-1 rounded-full text-xs font-semibold border transition-all shrink-0">
                {{ $category }} (<span x-text="pages.filter(p => p.category === '{{ $category }}').length"></span>)
            </button>
        @endforeach
    </div>

    <!-- Wiki Cards Grid -->
    <template x-if="filteredPages.length > 0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="page in filteredPages" :key="page.id">
                <div class="glass-panel glass-panel-hover p-4 rounded-xl flex flex-col justify-between relative group">
                    <div>
                        <!-- Category Badge & Actions -->
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20" x-text="page.category"></span>
                            <span class="text-[10px] text-slate-400" x-text="page.updated_at"></span>
                        </div>

                        <!-- Title -->
                        <a :href="page.url" class="block font-['Cinzel'] text-base font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-1 mb-1.5" x-text="page.title"></a>

                        <!-- Excerpt -->
                        <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-3" x-text="page.excerpt || 'Nessun contenuto...'"></p>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-3 border-t border-white/[0.08] flex items-center justify-between text-xs">
                        <a :href="page.url" class="text-amber-400 hover:text-amber-300 font-semibold inline-flex items-center gap-1">
                            <span>Leggi</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <div class="flex items-center gap-2">
                            <a :href="page.revisions_url" class="text-slate-400 hover:text-slate-200 text-[11px] flex items-center gap-1" title="Cronologia Revisioni">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Revisioni</span>
                            </a>
                            <a :href="page.edit_url" class="btn-secondary !p-1.5 !rounded-lg text-slate-300 hover:text-amber-400" title="Modifica">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Empty State -->
    <template x-if="filteredPages.length === 0">
        <div class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
            <svg class="w-12 h-12 text-slate-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna pagina trovata</h4>
            <p class="text-xs text-slate-400 mb-4">Non ci sono pagine wiki corrispondenti ai filtri o alla ricerca corrente.</p>
            <a href="{{ route('wiki.create', $campaign) }}" class="btn-primary text-xs py-2 px-3">
                <span>Crea Pagina Wiki</span>
            </a>
        </div>
    </template>
</div>
