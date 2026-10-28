@extends('layouts.app')

@section('title', 'Modifica: ' . $wiki->title . ' - ' . $campaign->name)

@section('content')
<div class="max-w-4xl mx-auto my-4 sm:my-8">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="hover:text-white transition-colors">{{ $wiki->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-medium">Modifica</span>
    </div>

    <div class="glass-panel p-5 sm:p-8 rounded-2xl">
        <div class="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-white/[0.08]">
            <div>
                <h1 class="font-['Cinzel'] text-xl sm:text-2xl font-bold text-white">Modifica Scheda</h1>
                <p class="text-xs text-slate-400 mt-1">Ogni modifica genera una nuova revisione sicura consultabile in cronologia.</p>
            </div>
            <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-1.5 px-3">Annulla</a>
        </div>

        <form method="POST" action="{{ route('wiki.update', [$campaign, $wiki]) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Titolo della Scheda *</label>
                    <input type="text" name="title" value="{{ old('title', $wiki->title) }}" required
                           class="input-premium text-base font-semibold" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Categoria</label>
                    <select name="category" class="input-premium text-sm">
                        @foreach($defaultCategories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $wiki->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Content & Preview -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Contenuto *</label>
                <x-markdown-editor :campaign="$campaign" :value="old('content', $wiki->content)" min-height="22rem"
                                   placeholder="Scrivi qui la descrizione. Digita [[ per collegare un'altra pagina della wiki." />
                <p class="mt-1 text-[11px] text-slate-500">Salvato come Markdown. Usa [[Titolo pagina]] per creare link tra le schede.</p>
            </div>

            <!-- Summary of edits -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Motivo o Sommario della Revisione (opzionale)</label>
                <input type="text" name="summary" value="{{ old('summary') }}" 
                       class="input-premium text-xs" placeholder="es. Aggiunto segreto sulla stirpe elfica o modificati punti ferita" />
            </div>

            <div class="pt-4 border-t border-white/[0.08] flex items-center justify-end gap-3">
                <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-2 px-4">Annulla</a>
                <button type="submit" class="btn-primary text-xs py-2 px-5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Salva Nuova Revisione</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
