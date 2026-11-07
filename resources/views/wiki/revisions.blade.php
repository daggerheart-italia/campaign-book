@extends('layouts.app')

@section('title', 'Cronologia Revisioni: ' . $wiki->title)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8 space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="hover:text-white transition-colors">{{ $wiki->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-medium">Cronologia Revisioni</span>
    </div>

    <!-- Header -->
    <div class="glass-panel p-6 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-['Cinzel'] text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                <x-app-icon name="clock-3" class="w-6 h-6 text-amber-400" />
                <span>Cronologia Revisioni</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Solo i master possono visualizzare e ripristinare le revisioni.</p>
        </div>

        <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-2 px-3 self-start sm:self-auto">
            <x-icon-label icon="arrow-left" label="Torna all'articolo" />
        </a>
    </div>

    <!-- Timeline of revisions -->
    <div class="space-y-4">
        @foreach($wiki->revisions as $index => $rev)
            @php $isCurrent = ($index === 0); @endphp
            <div class="glass-panel p-5 rounded-2xl relative {{ $isCurrent ? 'border-amber-500/40 shadow-md shadow-amber-500/5' : '' }}" x-data="{ open: false }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $isCurrent ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-white/5 text-slate-400 border border-white/10' }}">
                            #{{ $wiki->revisions->count() - $index }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-white">{{ $rev->summary ?: 'Modifica contenuto' }}</span>
                                @if($isCurrent)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">Versione Attuale</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $rev->created_at->format('d/m/Y H:i:s') }} ({{ $rev->created_at->diffForHumans() }}) • Modificato da <strong class="text-slate-200">{{ $rev->user ? $rev->user->name : 'Sconosciuto' }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <button type="button" @click="open = !open" class="btn-secondary !py-1.5 !px-3 text-xs">
                            <span class="inline-flex items-center gap-2">
                                <x-app-icon name="eye" x-show="!open" />
                                <x-app-icon name="eye-off" x-show="open" x-cloak />
                                <span class="icon-label-mobile" x-text="open ? 'Nascondi Contenuto' : 'Visualizza Contenuto'"></span>
                            </span>
                        </button>

                        @if(!$isCurrent)
                            <form method="POST" action="{{ route('wiki.restoreRevision', [$campaign, $wiki, $rev]) }}" onsubmit="return confirm('Ripristinare questa versione? Verrà salvata come nuova revisione.')" class="inline">
                                @csrf
                                <button type="submit" class="btn-primary !py-1.5 !px-3 text-xs">
                                    <x-icon-label icon="rotate-ccw" label="Ripristina" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Preview Content Box -->
                <div x-show="open" x-transition class="mt-4 pt-4 border-t border-white/[0.08]" x-cloak>
                    <div class="p-4 rounded-xl bg-black/40 border border-white/5">
                        <div class="text-xs text-amber-300 font-bold mb-2">Titolo: {{ $rev->title }} | Categoria: {{ $rev->category }}</div>
                        <div class="prose-dagger text-xs sm:text-sm">
                            {!! $campaign->renderMarkdown($rev->content) !!}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
