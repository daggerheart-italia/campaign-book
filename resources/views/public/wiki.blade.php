@extends('layouts.public')

@section('title', $wiki->title.' - '.$campaign->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('public.show', $campaign->public_slug) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('public.show', ['slug' => $campaign->public_slug, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <span class="text-amber-400 font-medium truncate">{{ $wiki->title }}</span>
    </div>

    <article class="glass-panel p-6 sm:p-10 rounded-2xl">
        <div class="pb-5 mb-6 border-b border-white/[0.08]">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                {{ $wiki->category ?: 'Generale' }}
            </span>
            <h1 class="mt-2 font-['Cinzel'] text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-wide">{{ $wiki->title }}</h1>
        </div>

        <div class="prose-dagger text-slate-200 text-sm sm:text-base leading-relaxed">
            {!! $campaign->renderMarkdown($wiki->content, true) !!}
        </div>
    </article>

    @if($wiki->parent || $childPages->isNotEmpty())
        <nav aria-label="Schede correlate" class="glass-panel rounded-xl p-3 sm:p-4 space-y-3">
            @if($wiki->parent)
                <div>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Pagina genitore</p>
                    <a href="{{ route('public.wiki', [$campaign->public_slug, $wiki->parent]) }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-amber-300 transition-colors hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-200">
                        <x-app-icon name="chevron-left" class="h-4 w-4 shrink-0" />
                        <span>{{ $wiki->parent->title }}</span>
                    </a>
                </div>
            @endif

            @if($childPages->isNotEmpty())
                <div>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Sottopagine</p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach($childPages as $childPage)
                            <li>
                                <a href="{{ route('public.wiki', [$campaign->public_slug, $childPage]) }}"
                                   class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-amber-300 transition-colors hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-200">
                                    <span>{{ $childPage->title }}</span>
                                    <x-app-icon name="chevron-right" class="h-4 w-4 shrink-0 text-amber-400/80" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </nav>
    @endif
</div>
@endsection
