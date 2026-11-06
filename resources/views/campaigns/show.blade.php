@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
<div x-data="{ 
    tab: (new URLSearchParams(window.location.search)).get('tab') || 'wiki',
    stats: {
        wiki_count: {{ $campaign->wikiPages->count() }},
        notes_count: {{ $campaign->sessionNotes->count() }},
        maps_count: {{ $campaign->maps->count() }},
        users_count: {{ $campaign->users->count() }},
    },
    setTab(newTab) {
        this.tab = newTab;
        const url = new URL(window.location);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
        window.campaignSyncSetActiveEntity?.(this.entityForTab(newTab));
        if (newTab === 'map') {
            setTimeout(() => {
                if (window.campaignSyncManager && window.campaignSyncManager.entities && window.campaignSyncManager.entities['map']) {
                    window.campaignSyncManager.syncMapsOnly?.();
                }
            }, 50);
        }
    },
    entityForTab(tab) {
        return { wiki: 'wiki', map: 'pin', notes: 'note' }[tab] || null;
    },
    init() {
        window.campaignSyncSetActiveEntity?.(this.entityForTab(this.tab));
    }
}" @campaign-stats-updated.window="stats = $event.detail" class="space-y-6">

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
                        <x-app-icon name="users" class="w-3.5 h-3.5 text-slate-400" />
                        <span>{{ $campaign->users->count() }} membri</span>
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <h1 class="font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">
                        {{ $campaign->name }}
                    </h1>

                    @if($isGm)
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-secondary !p-2 sm:!px-3 shrink-0">
                            <x-icon-label icon="pencil" label="Modifica" />
                        </a>
                    @endif
                </div>

                @if($campaign->description)
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                        {{ $campaign->description }}
                    </p>
                @endif
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-col items-stretch gap-2.5 shrink-0">
                <div>
                    <a href="{{ route('campaigns.export', $campaign) }}" class="btn-secondary w-full justify-center whitespace-nowrap text-xs py-2 px-3" title="Esporta l'intero compendio in formato Markdown">
                        <x-icon-label icon="download" label="Esporta Markdown" />
                    </a>
                </div>
                @if($isGm)
                    <div>
                        <button @click="setTab('members')" class="btn-secondary w-full justify-center whitespace-nowrap text-xs py-2 px-3" title="Codici e link di invito">
                            <x-icon-label icon="user-round-plus" label="Invita" />
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile-First Tab Navigation -->
    <div class="glass-panel p-1.5 rounded-xl overflow-x-auto scrollbar-none flex items-center gap-1">
        <button @click="setTab('wiki')" 
                :class="tab === 'wiki' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="inline-flex items-center gap-2 px-3 sm:px-4 min-h-11 rounded-lg text-xs sm:text-sm font-semibold border transition-all duration-200 whitespace-nowrap shrink-0">
            <x-icon-label icon="book-open" label="Wiki & Grimorio" />
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300" x-text="stats.wiki_count"></span>
        </button>

        <button @click="setTab('map')" 
                :class="tab === 'map' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-3 sm:px-4 min-h-11 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <x-icon-label icon="map" label="Mappa" />
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300" x-text="stats.maps_count"></span>
        </button>

        <button @click="setTab('notes')" 
                :class="tab === 'notes' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-3 sm:px-4 min-h-11 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <x-icon-label icon="pencil" label="Note" />
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300" x-text="stats.notes_count"></span>
        </button>

        <button @click="setTab('members')" 
                :class="tab === 'members' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                class="flex items-center gap-2 px-3 sm:px-4 min-h-11 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0">
            <x-icon-label icon="users" label="Membri" />
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-slate-300" x-text="stats.users_count"></span>
        </button>

        @if($isGm)
            <button @click="setTab('trash')" 
                    :class="tab === 'trash' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                    class="flex items-center gap-2 px-3 sm:px-4 min-h-11 rounded-lg text-xs sm:text-sm font-semibold transition-all border shrink-0 sm:ml-auto">
                <x-icon-label icon="trash-2" label="Cestino & Revisioni" />
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

    <script>
        (function initCampaignSyncManager() {
            if (!window.createCampaignSyncManager) {
                setTimeout(initCampaignSyncManager, 50);
                return;
            }
            @php
                $initialTab = request()->query('tab', 'wiki');
                $initialEntity = match ($initialTab) {
                    'wiki' => 'wiki',
                    'map' => 'pin',
                    'notes' => 'note',
                    default => null,
                };
            @endphp
            window.campaignSyncManager = window.createCampaignSyncManager({
                campaignId: {{ $campaign->id }},
                syncDeltaUrl: '{{ route('campaigns.syncDelta', $campaign) }}',
                snapshotUrls: {
                    note: '{{ route('notes.snapshot', $campaign) }}',
                    pin: '{{ route('pins.snapshot', $campaign) }}{{ $activeMap ? '?map_id=' . $activeMap->id : '' }}',
                    wiki: '{{ route('wiki.list.snapshot', $campaign) }}',
                    map: '{{ route('maps.snapshot', $campaign) }}',
                },
                initialCursors: {
                    note: @js($notesSyncCursor ?? null),
                    pin: @js($pinsSyncCursor ?? null),
                    wiki: @js($wikiSyncCursor ?? null),
                    map: @js($mapsSyncCursor ?? null),
                },
                initialActiveEntity: @js($initialEntity),
                pollIntervalActiveMs: 5000,
                pollIntervalInactiveMs: 60000,
            });
            window.campaignSyncManager.init();
            // Ensure map list stays in sync globally
            window.campaignSyncManager.registerEntity('map', {
                applyChange: () => {},
                onStatsUpdate: () => {},
                onReset: () => {},
            });
            window.campaignSyncManager.start();
            document.addEventListener('visibilitychange', () => {
                window.campaignSyncManager?.handleVisibilityChange();
            });

            window.campaignSyncSetActiveEntity = function(entityType) {
                if (window.campaignSyncManager) {
                    window.campaignSyncManager.setActiveEntity(entityType);
                }
            };
        })();
    </script>

</div>
@endsection
