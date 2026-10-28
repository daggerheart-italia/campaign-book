@extends('layouts.app')

@section('title', 'Modifica: ' . $campaign->name)

@section('content')
<div class="max-w-2xl mx-auto my-6 sm:my-10">
    <div class="mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Torna alla campagna</span>
        </a>
    </div>

    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-1.5">Modifica Campagna</h1>
            <p class="text-sm text-slate-400">Aggiorna il nome e la descrizione della campagna.</p>
        </div>

        <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Nome della Campagna *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $campaign->name) }}" required autofocus
                       class="input-premium text-base" />
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Descrizione & Incipit</label>
                <textarea id="description" name="description" rows="4"
                          class="input-premium resize-y">{{ old('description', $campaign->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('campaigns.show', $campaign) }}" class="btn-secondary w-full sm:w-auto text-sm order-2 sm:order-1">Annulla</a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm order-1 sm:order-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Salva modifiche</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
