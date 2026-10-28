@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
<div x-data="{ 
    tab: (new URLSearchParams(window.location.search)).get('tab') || 'wiki',
    setTab(newTab) {
        this.tab = newTab;
        const url = new URL(window.location);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
    }
}" class="space-y-6">

    <!-- Workspace Banner & Header -->
    <div class="glass-panel p-5 sm:p-7 rounded-2xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isGm ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isGm ? 'bg-amber-400' : 'bg-indigo-400' }}"></span>
                        {{ $isGm ? 'Game Master' : 'Giocatore' }}
                    </span>
                    <span class="text-xs text-slate-400 bg-white/5 px-2.5 py-0.5 rounded-full border border-white/10">
                        {{ $campaign->system }}
                    </span>
                    <span class="text-xs text-slate-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>{{ $campaign->users->count() }} membri</span>
                    </span>
                </div>

                <h1 class="font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">
                    {{ $campaign->name }}
                </h1>

                @if($campaign->description)
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                        {{ $campaign->description }}
                    </p>
                @endif
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                @if($isGm)
                    <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-secondary text-xs py-2 px-3" title="Modifica nome e descrizione della campagna">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Modifica</span>
                    </a>
                @endif

                <a href="{{ route('campaigns.export', $campaign) }}" class="btn-secondary text-xs py-2 px-3" title="Esporta l'intero compendio in formato Markdown">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Esporta Markdown</span>
                </a>

                <button @click="setTab('members')" class="btn-secondary text-xs py-2 px-3" title="Codici e link di invito">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>Invita</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile-First Tab Navigation -->
    <div class="glass-panel p-1.5 rounded-xl overflow-x-auto scrollbar-none flex items-center gap-1">
        <button @click="setTab('wiki')" 
                :class="tab === 'wiki' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <span>Wiki</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300">{{ $campaign->wikiPages->count() }}</span>
        </button>

        <button @click="setTab('map')" 
                :class="tab === 'map' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Mappa</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300">{{ $campaign->maps->count() }}</span>
        </button>

        <button @click="setTab('notes')" 
                :class="tab === 'notes' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Note</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300">{{ $campaign->sessionNotes->count() }}</span>
        </button>

        <button @click="setTab('members')" 
                :class="tab === 'members' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Membri</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300">{{ $campaign->users->count() }}</span>
        </button>

        @if($isGm)
            <button @click="setTab('trash')" 
                    :class="tab === 'trash' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0 ml-auto">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span>Cestino & Revisioni</span>
                @php $totalTrashed = $trashedWikiPages->count() + $trashedMapPins->count() + $trashedSessionNotes->count(); @endphp
                @if($totalTrashed > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/30 text-rose-300 font-bold">{{ $totalTrashed }}</span>
                @endif
            </button>
        @endif
    </div>

    <!-- Tab Contents -->
    <div>
        <div x-show="tab === 'wiki'">
            @include('campaigns.partials.wiki')
        </div>

        <div x-show="tab === 'map'" x-cloak>
            @include('campaigns.partials.map')
        </div>

        <div x-show="tab === 'notes'" x-cloak>
            @include('campaigns.partials.notes')
        </div>

        <div x-show="tab === 'members'" x-cloak>
            @include('campaigns.partials.members')
        </div>

        @if($isGm)
            <div x-show="tab === 'trash'" x-cloak>
                @include('campaigns.partials.trash')
            </div>
        @endif
    </div>

</div>
@endsection
