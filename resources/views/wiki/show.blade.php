@extends('layouts.app')

@section('title', $wiki->title . ' - ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8 space-y-6"
     x-data="wikiPageSync({{ Js::from([
        'url' => route('wiki.sync', [$campaign, $wiki]),
        'updatedAt' => $wiki->updated_at?->toIso8601String(),
        'updatedBy' => $wiki->editor?->name,
     ]) }})"
     x-init="init()">
    <div x-show="hasUpdate" class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-100" role="status" x-cloak>
        <p><strong x-text="updatedBy || 'Un membro della campagna'"></strong> ha aggiornato questa scheda.</p>
        <button type="button" class="mt-2 text-xs font-semibold underline" @click="window.location.reload()">
            <x-icon-label icon="refresh-cw" label="Ricarica la versione più recente" />
        </button>
    </div>

    <!-- Breadcrumb navigation -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <span class="text-amber-400 font-medium truncate">{{ $wiki->title }}</span>
    </div>

    <!-- Article Glass Container -->
    <article class="glass-panel p-6 sm:p-10 rounded-2xl relative overflow-hidden">
        <!-- Glow effect -->
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-white/[0.08]">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                        {{ $wiki->category }}
                    </span>

                    @if($wiki->mapPins->isNotEmpty())
                        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'map']) }}" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-300 border border-rose-500/30 hover:bg-rose-500/25 transition-colors">
                            <x-app-icon name="map-pin" class="w-3 h-3 text-rose-400" />
                            <span>{{ $wiki->mapPins->count() }} punto sulla mappa</span>
                        </a>
                    @endif
                </div>

                <h1 class="font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">
                    {{ $wiki->title }}
                </h1>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 shrink-0 overflow-x-auto scrollbar-none">
                @if($campaign->isGm(auth()->user()))
                    <a href="{{ route('wiki.revisions', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-2 px-3 shrink-0" title="Visualizza revisioni storiche">
                        <x-icon-label icon="clock-3" label="Revisioni ({{ $wiki->revisions->count() }})" />
                    </a>
                @endif

                <a href="{{ route('wiki.edit', [$campaign, $wiki]) }}" class="btn-primary text-xs py-2 px-4 shrink-0">
                    <x-icon-label icon="pencil" label="Modifica" />
                </a>

                <form method="POST" action="{{ route('wiki.destroy', [$campaign, $wiki]) }}" onsubmit="return confirm('Spostare questa scheda nel cestino? Potrà essere ripristinata dal GM.')" class="inline shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger !p-2 sm:!px-3 text-xs" title="Sposta nel cestino">
                        <x-icon-label icon="trash-2" label="Elimina" />
                    </button>
                </form>
            </div>
        </div>

        <!-- Meta info bar -->
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 py-3 mb-6 border-b border-white/[0.05]">
            <span class="flex items-center gap-1.5">
                <x-app-icon name="user-round" class="w-3.5 h-3.5 text-slate-500" />
                <span>Creato da <strong class="text-slate-200">{{ $wiki->creator ? $wiki->creator->name : 'Sconosciuto' }}</strong></span>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1.5">
                <x-app-icon name="pencil" class="w-3.5 h-3.5 text-slate-500" />
                <span>Ultima modifica: <strong class="text-slate-200">{{ $wiki->updated_at->diffForHumans() }}</strong></span>
            </span>
        </div>

        <!-- Markdown Rendered Content -->
        <div class="prose-dagger text-slate-200 text-sm sm:text-base leading-relaxed">
            {!! $campaign->renderMarkdown($wiki->content) !!}
        </div>
    </article>

    @if($wiki->parent || $childPages->isNotEmpty())
        <nav aria-label="Navigazione tra schede correlate" class="space-y-3">
            @if($wiki->parent)
                <a href="{{ route('wiki.show', [$campaign, $wiki->parent]) }}"
                   class="inline-flex min-h-11 items-center gap-2 text-sm text-amber-300 hover:text-amber-200 transition-colors">
                    <x-app-icon name="chevron-left" class="w-4 h-4 shrink-0" />
                    <span>Genitore:</span>
                    <span class="font-semibold">{{ $wiki->parent->title }}</span>
                </a>
            @endif

            @if($childPages->isNotEmpty())
                <details class="rounded-xl border border-white/10 bg-white/[0.03]">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-sm text-slate-200 [&::-webkit-details-marker]:hidden">
                        <span>Schede figlie</span>
                        <span class="inline-flex items-center gap-2 text-xs text-slate-400">
                            {{ $childPages->count() }}
                            <x-app-icon name="chevron-down" class="w-4 h-4" />
                        </span>
                    </summary>
                    <ul class="flex flex-wrap gap-2 border-t border-white/10 px-3 py-3">
                        @foreach($childPages as $childPage)
                            <li>
                                <a href="{{ route('wiki.show', [$campaign, $childPage]) }}"
                                   class="inline-flex min-h-11 items-center rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-amber-300 transition-colors hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-200">
                                    {{ $childPage->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </nav>
    @endif

    <!-- Back button -->
    <div class="flex justify-between items-center pt-2">
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="btn-secondary text-xs py-2 px-4">
            <x-icon-label icon="arrow-left" label="Torna alla Wiki" />
        </a>
    </div>

</div>

<script>
function wikiPageSync(config) {
    return {
        lastUpdatedAt: config.updatedAt,
        updatedBy: config.updatedBy,
        hasUpdate: false,
        init() {
            this.syncTimer = window.setInterval(() => this.checkForUpdates(), 10000);
        },
        destroy() {
            window.clearInterval(this.syncTimer);
        },
        async checkForUpdates() {
            try {
                const response = await fetch(config.url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error(`Sincronizzazione Wiki non riuscita (${response.status}).`);
                const data = await response.json();
                if (data.updated_at && data.updated_at !== this.lastUpdatedAt) {
                    this.updatedBy = data.updated_by?.name || 'Un membro della campagna';
                    this.hasUpdate = true;
                }
            } catch (error) {
                console.error(error);
            }
        },
    };
}
</script>
@endsection
