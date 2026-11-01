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
        <nav aria-label="Schede correlate" class="flex flex-wrap gap-2 text-sm">
            @if($wiki->parent)
                <a href="{{ route('public.wiki', [$campaign->public_slug, $wiki->parent]) }}" class="text-amber-300 hover:text-amber-200">← {{ $wiki->parent->title }}</a>
            @endif
            @foreach($childPages as $childPage)
                <a href="{{ route('public.wiki', [$campaign->public_slug, $childPage]) }}"
                   class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-amber-300 hover:bg-amber-500/10">{{ $childPage->title }}</a>
            @endforeach
        </nav>
    @endif
</div>
@endsection
