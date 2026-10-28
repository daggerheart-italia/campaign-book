<div x-data="{ page: pages.find(candidate => candidate.id === {{ $node['page_id'] }}), expanded: false }"
     x-show="matchesPage(page)"
     data-wiki-page-id="{{ $node['page_id'] }}"
     class="relative isolate space-y-3">
    @if(count($node['children']) > 0)
        <div x-show="!expanded" x-transition aria-hidden="true"
             class="absolute inset-0 translate-x-1 translate-y-1 rounded-xl border border-white/15 border-r-2 border-b-2 bg-slate-900 pointer-events-none"></div>
        <div x-show="!expanded" x-transition aria-hidden="true"
             class="absolute inset-0 translate-x-2 translate-y-2 rounded-xl border border-white/15 border-r-2 border-b-2 bg-slate-900/95 pointer-events-none"></div>
    @endif

    @include('campaigns.partials.wiki-page-card-content', ['childrenCount' => count($node['children'])])

    @if(count($node['children']) > 0)
        <div x-show="expanded" x-transition class="ml-3 border-l border-white/10 pl-3 space-y-3">
            @foreach($node['children'] as $childNode)
                @include('campaigns.partials.wiki-page-card', ['node' => $childNode])
            @endforeach
        </div>
    @endif
</div>
