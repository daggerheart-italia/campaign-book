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
                        <x-app-icon name="map-pin" class="w-3.5 h-3.5 text-rose-400" />
                        <span>Verrà collegata automaticamente al segnaposto <strong>{{ $pin->title }}</strong></span>
                    </div>
                @endif
            </div>
            <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => isset($pin) ? 'map' : 'wiki']) }}" class="btn-secondary text-xs py-1.5 px-3 self-start sm:self-auto"><x-icon-label icon="x" label="Annulla" /></a>
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
                <x-parent-page-select :parentPages="$parentPages" :selected="old('parent_id')" />
            </div>

            <!-- Editor & Live Preview Switcher -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Contenuto *</label>
                <x-markdown-editor :campaign="$campaign" :value="old('content', $prefillContent ?? '')" min-height="22rem"
                                   placeholder="Scrivi qui la descrizione. Digita [[ per collegare un'altra pagina della wiki." />
                <p class="mt-1 text-[11px] text-slate-500">Salvato come Markdown. Usa [[Titolo pagina]] per creare link tra le schede.</p>
            </div>

            <div class="pt-4 border-t border-white/[0.08] flex items-center justify-end gap-3">
                <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => isset($pin) ? 'map' : 'wiki']) }}" class="btn-secondary text-xs py-2 px-4"><x-icon-label icon="x" label="Annulla" /></a>
                <button type="submit" class="btn-primary text-xs py-2 px-5">
                    <x-icon-label icon="check" label="Salva Scheda Wiki" />
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
