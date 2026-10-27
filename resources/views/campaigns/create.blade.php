@extends('layouts.app')

@section('title', 'Crea Nuova Campagna')

@section('content')
<div class="max-w-2xl mx-auto my-6 sm:my-10">
    <div class="mb-4">
        <a href="{{ route('campaigns.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Torna alle campagne</span>
        </a>
    </div>

    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-amber-600 to-amber-400 p-[1px] shadow-lg shadow-amber-500/20 mx-auto mb-3">
                <div class="w-full h-full bg-[#0d121f] rounded-[11px] flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
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
                <a href="{{ route('campaigns.index') }}" class="btn-secondary w-full sm:w-auto text-sm order-2 sm:order-1">Annulla</a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm order-1 sm:order-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Crea e Apri Grimorio</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
