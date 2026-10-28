@props([
    'campaign',
    'name' => 'content',
    'value' => '',
    'placeholder' => '',
    'minHeight' => '12rem',
])

@php
    $pages = $campaign->wikiPages
        ->sortBy('title')
        ->map(fn ($page) => ['title' => $page->title, 'category' => $page->category])
        ->values();
@endphp

<div {{ $attributes }}
     x-data="markdownEditor({ value: @js($value), placeholder: @js($placeholder), pages: @js($pages) })"
     @if($attributes->has('x-model')) x-modelable="value" @endif
     class="md-editor" style="--md-editor-min-height: {{ $minHeight }}">
    <div class="md-editor-toolbar">
        <template x-if="mode === 'visual'">
            <div class="flex flex-wrap items-center gap-1">
                <button type="button" title="Grassetto" :class="{ 'is-active': active.bold }" @click="run('bold')"><strong>B</strong></button>
                <button type="button" title="Corsivo" :class="{ 'is-active': active.italic }" @click="run('italic')"><em>I</em></button>
                <button type="button" title="Titolo" :class="{ 'is-active': active.h2 }" @click="run('h2')">H2</button>
                <button type="button" title="Sottotitolo" :class="{ 'is-active': active.h3 }" @click="run('h3')">H3</button>
                <button type="button" title="Elenco puntato" :class="{ 'is-active': active.bullet }" @click="run('bullet')">• Elenco</button>
                <button type="button" title="Elenco numerato" :class="{ 'is-active': active.ordered }" @click="run('ordered')">1. Elenco</button>
                <button type="button" title="Citazione" :class="{ 'is-active': active.quote }" @click="run('quote')">❝</button>
                <button type="button" title="Collega a una pagina wiki (oppure digita [[)" @click="run('wikilink')">[[ Pagina ]]</button>
            </div>
        </template>

        <div class="ml-auto inline-flex p-0.5 rounded-lg bg-black/40 border border-white/10 text-xs">
            <button type="button" @click="setMode('visual')" :class="mode === 'visual' ? 'bg-amber-500/20 text-amber-300 font-bold' : ''">Visuale</button>
            <button type="button" @click="setMode('markdown')" :class="mode === 'markdown' ? 'bg-amber-500/20 text-amber-300 font-bold' : ''">Markdown</button>
        </div>
    </div>

    <div x-show="mode === 'visual'" class="md-editor-surface prose-dagger" x-ref="surface"></div>

    <textarea name="{{ $name }}" x-model="value" x-show="mode === 'markdown'" rows="12"
              class="md-editor-source" x-cloak></textarea>
</div>
