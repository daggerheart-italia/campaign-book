@extends('layouts.app')

@section('title', 'Nuova Scheda Wiki - ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8">
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

            <div>
                <label for="parent_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Scheda genitore</label>
                <select id="parent_id" name="parent_id" class="input-premium text-sm">
                    <option value="">Nessun genitore</option>
                    @foreach($parentPages as $parentPage)
                        <option value="{{ $parentPage->id }}" {{ (string) old('parent_id') === (string) $parentPage->id ? 'selected' : '' }}>
                            {{ $parentPage->category ?: 'Generale' }} — {{ $parentPage->title }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Editor & Live Preview Switcher -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Contenuto *</label>
                <x-markdown-editor :campaign="$campaign" :value="old('content', $prefillContent ?? '')" min-height="22rem"
                                   placeholder="Scrivi qui la descrizione. Digita [[ per collegare un'altra pagina della wiki." />
                <p class="mt-1 text-[11px] text-slate-500">Salvato come Markdown. Usa [[Titolo pagina]] per creare link tra le schede.</p>
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
