@extends('layouts.app')

@section('title', 'Crea Nuova Campagna')

@section('content')
<div class="max-w-2xl mx-auto my-6 sm:my-10">
    <div class="mb-4">
        <a href="{{ route('campaigns.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <x-icon-label icon="arrow-left" label="Torna alle campagne" />
        </a>
    </div>

    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-amber-600 to-amber-400 p-[1px] shadow-lg shadow-amber-500/20 mx-auto mb-3">
                <div class="w-full h-full bg-[#0d121f] rounded-[11px] flex items-center justify-center">
                    <x-app-icon name="book-open" class="w-6 h-6 text-amber-400" />
                </div>
            </div>
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-1.5">Nuova Campagna</h1>
            <p class="text-sm text-slate-400">Creando la campagna diventerai automaticamente il Game Master.</p>
        </div>

        <form method="POST" action="{{ route('campaigns.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Nome della Campagna *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="input-premium text-base" placeholder="es. I Segreti del Bosco d'Ambra" />
            </div>

            <div>
                <label for="system" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Sistema di Gioco</label>
                <input type="text" id="system" name="system" value="{{ old('system', 'Daggerheart') }}" required
                       class="input-premium" placeholder="Daggerheart" />
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Descrizione & Incipit</label>
                <textarea id="description" name="description" rows="4"
                          class="input-premium resize-y" placeholder="Descrivi il setting, il tono dell'avventura o l'incipit per i tuoi giocatori...">{{ old('description') }}</textarea>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('campaigns.index') }}" class="btn-secondary w-full sm:w-auto text-sm order-2 sm:order-1"><x-icon-label icon="x" label="Annulla" /></a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm order-1 sm:order-2">
                    <x-icon-label icon="check" label="Crea e Apri Grimorio" />
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
