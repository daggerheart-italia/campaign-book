<div x-data="{ page: pages.find(candidate => candidate.id === {{ $node['page_id'] }}) }"
     x-show="matchesPage(page)"
     data-wiki-page-id="{{ $node['page_id'] }}"
     class="relative">
    @include('campaigns.partials.wiki-page-card-content')
</div>
