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
     class="md-editor" style="--md-editor-min-height: {{ $minHeight }}"
     @submit.capture.prevent="if (showImageModal) { $event.stopPropagation(); return false; }">
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
                <button type="button" title="Inserisci immagine da URL pubblico (http/https)" @click="openImageModal('visual')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
            </div>
        </template>

        <template x-if="mode === 'markdown'">
            <div class="flex flex-wrap items-center gap-1">
                <button type="button" title="Inserisci immagine da URL pubblico (http/https)" @click="openImageModal('markdown')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
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

    <!-- Image insertion modal -->
    <template x-teleport="body">
        <div x-show="showImageModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="cancelImageModal()"></div>
            <div class="relative w-full max-w-md glass-panel rounded-2xl p-6 shadow-2xl border border-white/10" @keydown.escape.window.stop.prevent="cancelImageModal()">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 class="font-['Cinzel'] text-lg font-bold text-white">Inserisci immagine</h3>
                        <p class="text-xs text-slate-400 mt-1">Usa un URL pubblico (http/https). Il testo alternativo migliora l'accessibilità.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-white transition-colors" @click="cancelImageModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent.stop="confirmImageModal()" class="space-y-4" novalidate>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">URL immagine *</label>
                        <input type="url" x-ref="imageUrlInput" x-model.trim="imageUrl" placeholder="https://esempio.com/immagine.jpg" required
                               class="input-premium text-sm" @keydown.enter.prevent.stop="confirmImageModal()" />
                        <p class="mt-1 text-[11px] text-rose-300" x-show="imageError" x-text="imageError" x-cloak></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Testo alternativo (facoltativo)</label>
                        <input type="text" x-model="imageAlt" placeholder="Ritratto del personaggio" class="input-premium text-sm" @keydown.enter.prevent.stop="confirmImageModal()" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn-secondary text-xs py-2 px-4" @click.stop.prevent="cancelImageModal()">Annulla</button>
                        <button type="button" class="btn-primary text-xs py-2 px-4" @click.stop.prevent="confirmImageModal()">Inserisci immagine</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
