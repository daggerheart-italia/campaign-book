@extends('layouts.app')

@section('title', 'Modifica: ' . $campaign->name)

@section('content')
<div class="max-w-2xl mx-auto my-6 sm:my-10">
    <div class="mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <x-icon-label icon="arrow-left" label="Torna alla campagna" />
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
                <a href="{{ route('campaigns.show', $campaign) }}" class="btn-secondary w-full sm:w-auto text-sm order-2 sm:order-1"><x-icon-label icon="x" label="Annulla" /></a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm order-1 sm:order-2">
                    <x-icon-label icon="check" label="Salva modifiche" />
                </button>
            </div>
        </form>
    </div>

    <div class="glass-panel p-6 sm:p-8 rounded-2xl mt-6">
        <h2 class="font-['Cinzel'] text-xl font-bold text-white mb-1.5">Condivisione pubblica</h2>
        <p class="text-sm text-slate-400 mb-4">Chiunque abbia il link potrà consultare wiki, mappe e note di sessione in sola lettura, senza account. Lascia vuoto per disattivare.</p>

        <form method="POST" action="{{ route('campaigns.updateSharing', $campaign) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="public_slug" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Link pubblico</label>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 shrink-0">{{ url('/share') }}/</span>
                    <input type="text" id="public_slug" name="public_slug" value="{{ old('public_slug', $campaign->public_slug) }}"
                           maxlength="60" placeholder="la-mia-campagna" class="input-premium" />
                </div>
                @error('public_slug')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
                @if(session('public_slug_suggestions'))
                    <div class="mt-2">
                        <p class="text-xs text-slate-400 mb-1.5">Alternative plausibili:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(session('public_slug_suggestions') as $suggestion)
                                <button type="button" onclick="document.getElementById('public_slug').value='{{ $suggestion }}'; this.closest('form').submit();" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-600/60 transition-colors">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if($campaign->isPublic())
                <p class="text-xs text-slate-400">Link attivo: <a href="{{ route('public.show', $campaign->public_slug) }}" target="_blank" rel="noopener" class="text-amber-300 hover:text-amber-200 break-all">{{ route('public.show', $campaign->public_slug) }}</a></p>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-sm"><x-icon-label icon="check" label="Salva link" /></button>
            </div>
        </form>
    </div>
</div>
@endsection
