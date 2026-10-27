@extends('layouts.app')

@section('title', 'Nuova Scheda Wiki - ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8" x-data="{ 
    content: @js(old('content', $prefillContent ?? '')),
    activeTab: 'edit',
    get parsedHtml() {
        if (!this.content) return '<p class=\'text-slate-500 italic\'>L\'anteprima apparirà qui...</p>';
        try {
            return window.marked ? window.marked.parse(this.content) : this.content;
        } catch(e) {
            return this.content;
        }
    }
}">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <span class="text-amber-400 font-medium">Nuova Scheda</span>
    </div>

    <div class="glass-panel p-5 sm:p-8 rounded-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-white/[0.08]">
            <div>
                <h1 class="font-['Cinzel'] text-xl sm:text-2xl font-bold text-white">Nuova Scheda Wiki</h1>
                <p class="text-xs text-slate-400 mt-1">Descrivi un personaggio, luogo, fazione o segreto per la campagna.</p>
                
                @if(isset($pin) && $pin)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 mt-2.5 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Verrà collegata automaticamente al segnaposto <strong>{{ $pin->title }}</strong></span>
                    </div>
                @endif
            </div>
            <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => isset($pin) ? 'map' : 'wiki']) }}" class="btn-secondary text-xs py-1.5 px-3 self-start sm:self-auto">Annulla</a>
        </div>

        <form method="POST" action="{{ route('wiki.store', $campaign) }}" class="space-y-5">
            @csrf

            @if(isset($pinId) && $pinId)
                <input type="hidden" name="pin_id" value="{{ $pinId }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Titolo della Scheda *</label>
                    <input type="text" name="title" value="{{ old('title', $prefillTitle ?? '') }}" required autofocus
                           class="input-premium text-base font-semibold" placeholder="es. Cittadella di Kar-Drak" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Categoria</label>
                    <select name="category" class="input-premium text-sm">
                        @foreach($defaultCategories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $prefillCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Editor & Live Preview Switcher -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Contenuto (Markdown) *</label>
                    
                    <div class="inline-flex p-0.5 rounded-lg bg-black/40 border border-white/10 text-xs">
                        <button type="button" @click="activeTab = 'edit'" :class="activeTab === 'edit' ? 'bg-amber-500/20 text-amber-300 font-bold' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-md transition-all">Scrivi</button>
                        <button type="button" @click="activeTab = 'preview'" :class="activeTab === 'preview' ? 'bg-amber-500/20 text-amber-300 font-bold' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-md transition-all">Anteprima</button>
                    </div>
                </div>

                <div x-show="activeTab === 'edit'">
                    <textarea name="content" x-model="content" rows="14" required
                              class="input-premium font-mono text-sm leading-relaxed" 
                              placeholder="# Panoramica&#10;&#10;Scrivi qui la descrizione in formato Markdown..."></textarea>
                    <div class="mt-1 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Supporta formattazione Markdown: # Titoli, **grassetto**, *corsivo*, > citazioni, elenchi.</span>
                    </div>
                </div>

                <div x-show="activeTab === 'preview'" class="input-premium min-h-[350px] p-4 bg-slate-950/70 border-white/10 overflow-y-auto" style="display: none;">
                    <div class="prose-dagger" x-html="parsedHtml"></div>
                </div>
            </div>

            <div class="pt-4 border-t border-white/[0.08] flex items-center justify-end gap-3">
                <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => isset($pin) ? 'map' : 'wiki']) }}" class="btn-secondary text-xs py-2 px-4">Annulla</a>
                <button type="submit" class="btn-primary text-xs py-2 px-5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Salva Scheda Wiki</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
