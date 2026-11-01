@extends('layouts.public')

@section('title', $campaign->name)

@section('content')
@php
    $tabs = [
        'wiki' => ['label' => 'Wiki', 'count' => count($wikiItems)],
        'map' => ['label' => 'Mappa', 'count' => count($maps)],
        'notes' => ['label' => 'Note', 'count' => count($notes)],
    ];
@endphp
<div x-data="{
    tab: ['wiki', 'map', 'notes'].includes((new URLSearchParams(window.location.search)).get('tab')) ? (new URLSearchParams(window.location.search)).get('tab') : 'wiki',
    setTab(newTab) {
        this.tab = newTab;
        const url = new URL(window.location);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
    }
}" class="space-y-6">
    <section class="glass-panel p-5 sm:p-7 rounded-2xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <span class="text-xs text-slate-400 bg-white/5 px-2.5 py-0.5 rounded-full border border-white/10">{{ $campaign->system }}</span>
        <h1 class="mt-2 font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">{{ $campaign->name }}</h1>
        @if($campaign->description)
            <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed whitespace-pre-line">{{ $campaign->description }}</p>
        @endif
    </section>

    <div class="glass-panel p-1.5 rounded-xl overflow-x-auto scrollbar-none flex items-center gap-1" role="tablist">
        @foreach($tabs as $key => $item)
            <button type="button" role="tab" @click="setTab('{{ $key }}')"
                    :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
                <span>{{ $item['label'] }}</span>
                <span class="px-1.5 rounded-full text-[10px] bg-white/10 text-slate-300">{{ $item['count'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'wiki'" x-cloak>@include('public.partials.wiki')</div>
    <div x-show="tab === 'map'" x-cloak>@include('public.partials.map')</div>
    <div x-show="tab === 'notes'" x-cloak>@include('public.partials.notes')</div>
</div>
@endsection
