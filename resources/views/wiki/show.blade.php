@extends('layouts.app')

@section('title', $wiki->title . ' - ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8 space-y-6">

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
                            <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            <span>{{ $wiki->mapPins->count() }} punto sulla mappa</span>
                        </a>
                    @endif
                </div>

                <h1 class="font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">
                    {{ $wiki->title }}
                </h1>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('wiki.revisions', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-2 px-3" title="Visualizza revisioni storiche">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Revisioni ({{ $wiki->revisions->count() }})</span>
                </a>

                <a href="{{ route('wiki.edit', [$campaign, $wiki]) }}" class="btn-primary text-xs py-2 px-4">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Modifica</span>
                </a>

                <form method="POST" action="{{ route('wiki.destroy', [$campaign, $wiki]) }}" onsubmit="return confirm('Spostare questa scheda nel cestino? Potrà essere ripristinata dal GM.')" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger !p-2 text-xs" title="Sposta nel cestino">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Meta info bar -->
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 py-3 mb-6 border-b border-white/[0.05]">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Creato da <strong class="text-slate-200">{{ $wiki->creator ? $wiki->creator->name : 'Sconosciuto' }}</strong></span>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Ultima modifica: <strong class="text-slate-200">{{ $wiki->updated_at->diffForHumans() }}</strong></span>
            </span>
        </div>

        <!-- Markdown Rendered Content -->
        <div class="prose-dagger text-slate-200 text-sm sm:text-base leading-relaxed">
            {!! $campaign->renderMarkdown($wiki->content) !!}
        </div>
    </article>

    <!-- Back button -->
    <div class="flex justify-between items-center pt-2">
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="btn-secondary text-xs py-2 px-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Torna alla Wiki</span>
        </a>
    </div>

</div>
@endsection
