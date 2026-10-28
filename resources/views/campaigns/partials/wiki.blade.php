<div x-data="{
    search: '',
    selectedCategory: 'all',
    pages: {{ Js::from($wikiPageItems) }},
    pageById: {},
    navigationStack: [],
    init() {
        this.pageById = Object.fromEntries(this.pages.map(page => [page.id, page]));
    },
    get filteredPages() {
        return this.pages.filter(page => this.matchesPage(page));
    },
    get currentChildren() {
        const currentPageId = this.navigationStack[this.navigationStack.length - 1];
        const currentPage = this.pageById[currentPageId];

        return currentPage ? currentPage.children_ids.map(pageId => this.pageById[pageId]) : [];
    },
    hasActiveFilter() {
        return this.search.trim() !== '' || this.selectedCategory !== 'all';
    },
    openChildPages(pageId) {
        if (this.$refs.childrenDialog.open) {
            this.navigationStack.push(pageId);
        } else {
            this.navigationStack = [pageId];
            this.$refs.childrenDialog.showModal();
        }
    },
    navigateToLevel(index) {
        this.navigationStack = this.navigationStack.slice(0, index + 1);
    },
    closeChildPages() {
        this.navigationStack = [];
    },
    matchesPage(page) {
        const searchTerm = this.search.trim().toLocaleLowerCase();
        const matchesSearch = !searchTerm
            || page.title.toLocaleLowerCase().includes(searchTerm)
            || page.excerpt.toLocaleLowerCase().includes(searchTerm);
        const matchesCategory = this.selectedCategory === 'all' || page.category === this.selectedCategory;

        return matchesSearch && matchesCategory;
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
            <button @click="selectedCategory = {{ Js::from($category) }}"
                    :class="selectedCategory === {{ Js::from($category) }} ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                    class="px-3 py-1 rounded-full text-xs font-semibold border transition-all shrink-0">
                {{ $category }} (<span x-text="pages.filter(page => page.category === {{ Js::from($category) }}).length"></span>)
            </button>
        @endforeach
    </div>

    <dialog x-ref="childrenDialog"
            @click.self="$el.close()"
            @close="closeChildPages()"
            aria-labelledby="wiki-children-dialog-title"
            class="m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-2xl rounded-2xl border border-white/10 bg-slate-950 p-0 text-slate-100 shadow-2xl backdrop:bg-black/70 open:flex open:flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-white/10 p-4 sm:p-5">
            <div class="min-w-0">
                <h2 id="wiki-children-dialog-title" class="font-['Cinzel'] text-lg font-bold text-white sm:text-xl">
                    Schede figlie
                </h2>
                <nav aria-label="Percorso nella gerarchia wiki" class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-400">
                    <template x-for="(pageId, index) in navigationStack" :key="pageId">
                        <span class="inline-flex items-center gap-2">
                            <span aria-hidden="true" x-show="index > 0">/</span>
                            <button type="button" @click="navigateToLevel(index)"
                                    :aria-current="index === navigationStack.length - 1 ? 'location' : null"
                                    class="max-w-48 truncate hover:text-amber-300"
                                    :class="index === navigationStack.length - 1 ? 'text-amber-300' : ''"
                                    x-text="pageById[pageId].title"></button>
                        </span>
                    </template>
                </nav>
            </div>
            <button type="button" @click="$refs.childrenDialog.close()"
                    class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white"
                    aria-label="Chiudi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <ul class="grid min-h-0 grid-cols-1 gap-4 overflow-y-auto p-4 sm:grid-cols-2 sm:p-5">
            <template x-for="child in currentChildren" :key="child.id">
                <li x-data="{ page: child }" class="relative">
                    @include('campaigns.partials.wiki-page-card-content')
                </li>
            </template>
        </ul>
    </dialog>

    <!-- Wiki Cards Tree -->
    <template x-if="filteredPages.length > 0 && !hasActiveFilter()">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($wikiPageTree as $node)
                @include('campaigns.partials.wiki-page-card', ['node' => $node])
            @endforeach
        </div>
    </template>

    <!-- Flat Filtered Wiki Cards -->
    <template x-if="filteredPages.length > 0 && hasActiveFilter()">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="page in filteredPages" :key="page.id">
                <div class="relative isolate">
                    @include('campaigns.partials.wiki-page-card-content')
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
