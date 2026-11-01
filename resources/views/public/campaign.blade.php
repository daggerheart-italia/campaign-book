@extends('layouts.public')

@section('title', $campaign->name)

@section('content')
@php
    $defaultMap = $campaign->maps->first();
    $mapSections = $campaign->maps->map(function ($map) use ($campaign, $defaultMap) {
        $pins = $campaign->mapPins->filter(fn ($pin) => $pin->campaign_map_id === $map->id
            || ($pin->campaign_map_id === null && $map->is($defaultMap)));

        return ['title' => $map->title, 'url' => $map->image_url, 'pins' => $pins];
    });
    $orphanPins = $campaign->maps->isEmpty() ? $campaign->mapPins->whereNull('campaign_map_id') : collect();
    if ($orphanPins->isNotEmpty()) {
        $mapSections->push(['title' => 'Mappa', 'url' => asset('images/sample-fantasy-map.svg'), 'pins' => $orphanPins]);
    }
@endphp

<div class="space-y-10">
    <section class="glass-panel p-6 sm:p-8 rounded-2xl">
        <h1 class="font-['Cinzel'] text-3xl sm:text-4xl font-extrabold text-white">{{ $campaign->name }}</h1>
        <p class="mt-1 text-xs uppercase tracking-wider text-slate-400">{{ $campaign->system }}</p>
        @if($campaign->description)
            <p class="mt-4 text-slate-300 whitespace-pre-line">{{ $campaign->description }}</p>
        @endif
    </section>

    @if($wikiByCategory->isNotEmpty())
        <section class="space-y-4">
            <h2 class="font-['Cinzel'] text-xl font-bold text-amber-400">Wiki</h2>
            @foreach($wikiByCategory as $category => $pages)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">{{ $category }}</h3>
                    <ul class="flex flex-wrap gap-2">
                        @foreach($pages as $page)
                            <li>
                                <a href="{{ route('public.wiki', [$campaign->public_slug, $page]) }}"
                                   class="inline-flex rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-amber-300 hover:bg-amber-500/10">{{ $page->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </section>
    @endif

    @foreach($mapSections as $section)
        <section class="space-y-3">
            <h2 class="font-['Cinzel'] text-xl font-bold text-amber-400">{{ $section['title'] }}</h2>
            <div class="relative glass-panel rounded-2xl overflow-hidden">
                <img src="{{ $section['url'] }}" alt="{{ $section['title'] }}" class="block w-full h-auto">
                @foreach($section['pins'] as $pin)
                    <span class="absolute w-3.5 h-3.5 -ml-[7px] -mt-[7px] rounded-full border-2 border-white shadow"
                          style="left: {{ (float) $pin->x }}%; top: {{ (float) $pin->y }}%; background-color: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $pin->color) ? $pin->color : '#e11d48' }}"
                          title="{{ $pin->title }}"></span>
                @endforeach
            </div>
            @if($section['pins']->isNotEmpty())
                <ul class="grid sm:grid-cols-2 gap-2 text-sm">
                    @foreach($section['pins'] as $pin)
                        <li class="rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                            <p class="font-semibold text-white">{{ $pin->title }}</p>
                            @if($pin->description)
                                <p class="text-slate-400">{{ $pin->description }}</p>
                            @endif
                            @if($pin->wikiPage)
                                <a href="{{ route('public.wiki', [$campaign->public_slug, $pin->wikiPage]) }}" class="text-xs text-amber-300 hover:text-amber-200">{{ $pin->wikiPage->title }} →</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    @if($campaign->sessionNotes->isNotEmpty())
        <section class="space-y-4">
            <h2 class="font-['Cinzel'] text-xl font-bold text-amber-400">Note di sessione</h2>
            @foreach($campaign->sessionNotes as $note)
                <article class="glass-panel p-5 sm:p-6 rounded-2xl">
                    <h3 class="font-semibold text-white">{{ $note->title }}</h3>
                    <p class="text-xs text-slate-400 mb-3">{{ ($note->session_date ?? $note->created_at)->format('d/m/Y') }}</p>
                    <div class="prose-dagger text-slate-200 text-sm leading-relaxed">{!! $campaign->renderMarkdown($note->content, true) !!}</div>
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection
