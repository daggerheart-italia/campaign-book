@extends('layouts.app')

@section('title', 'Le mie Campagne')

@section('content')
<div class="space-y-6" x-data="{ joinModalOpen: false }">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-white/[0.08]">
        <div>
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white tracking-wide">Le tue Campagne</h1>
            <p class="text-sm text-slate-400 mt-1">Gestisci i tuoi mondi, consulta il grimorio e prepara la prossima sessione.</p>
        </div>

        <div class="flex items-center gap-2.5 justify-center">
            <button @click="joinModalOpen = true" class="btn-secondary text-sm">
                <x-icon-label icon="star-plus" label="Unisciti con Codice" showOnMobile="true" />
            </button>
        </div>
    </div>

    <!-- Campaign Cards Grid -->
    @if($campaigns->isEmpty())
        <div class="glass-panel p-10 sm:p-14 rounded-2xl text-center max-w-xl mx-auto my-8 border-dashed border-white/20">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-4">
                <x-app-icon name="book-open" class="w-8 h-8" />
            </div>
            <h3 class="font-['Cinzel'] text-xl font-bold text-white mb-2">Nessuna campagna attiva</h3>
            <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                Non fai ancora parte di nessuna campagna. Creane una nuova da Game Master oppure inserisci un codice invito fornito dal tuo gruppo.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('campaigns.create') }}" class="btn-primary w-full sm:w-auto">
                    <x-icon-label icon="plus" label="Crea la tua prima Campagna" showOnMobile="true" />
                </a>
                <button @click="joinModalOpen = true" class="btn-secondary w-full sm:w-auto">
                    <x-icon-label icon="key-round" label="Inserisci Codice Invito" showOnMobile="true" />
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($campaigns as $campaign)
                @php
                    $isGm = $campaign->isGm(Auth::user());
                @endphp
                <div class="glass-panel glass-panel-hover rounded-2xl p-5 flex flex-col justify-between relative group overflow-hidden">
                    <!-- Accent Glow on Hover -->
                    <div class="absolute -right-8 -top-8 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition-all pointer-events-none"></div>

                    <div>
                        <!-- Header Badges -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isGm ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isGm ? 'bg-amber-400' : 'bg-indigo-400' }}"></span>
                                {{ $isGm ? 'Game Master' : 'Giocatore' }}
                            </span>

                            <span class="text-[11px] font-medium text-slate-400 bg-white/5 px-2 py-0.5 rounded-md border border-white/10">
                                {{ $campaign->system }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h2 class="font-['Cinzel'] text-xl font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-1 mb-2">
                            {{ $campaign->name }}
                        </h2>

                        <!-- Description -->
                        <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed mb-4 min-h-[2rem]">
                            {{ $campaign->description ?: 'Nessuna descrizione specificata per questa avventura.' }}
                        </p>
                    </div>

                    <!-- Stats & Footer -->
                    <div class="pt-4 border-t border-white/[0.08] mt-2">
                        <div class="grid grid-cols-3 gap-2 text-center mb-4">
                            <div class="bg-white/[0.03] rounded-lg p-2 border border-white/[0.05]">
                                <div class="text-xs font-bold text-slate-200">{{ $campaign->wiki_pages_count }}</div>
                                <div class="text-[10px] text-slate-400 uppercase font-medium">Wiki</div>
                            </div>
                            <div class="bg-white/[0.03] rounded-lg p-2 border border-white/[0.05]">
                                <div class="text-xs font-bold text-slate-200">{{ $campaign->map_pins_count }}</div>
                                <div class="text-[10px] text-slate-400 uppercase font-medium">Pin Mappa</div>
                            </div>
                            <div class="bg-white/[0.03] rounded-lg p-2 border border-white/[0.05]">
                                <div class="text-xs font-bold text-slate-200">{{ $campaign->session_notes_count }}</div>
                                <div class="text-[10px] text-slate-400 uppercase font-medium">Note</div>
                            </div>
                        </div>

                        <a href="{{ route('campaigns.show', $campaign) }}" class="btn-primary w-full py-2.5 text-xs font-semibold justify-center">
                            <x-icon-label icon="book-open" label="Apri Grimorio" showOnMobile="true"/>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Join Campaign Modal -->
    <div x-show="joinModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-cloak>
        <div @click.away="joinModalOpen = false" class="glass-panel p-6 sm:p-8 rounded-2xl max-w-md w-full relative">
            <button @click="joinModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1" aria-label="Chiudi finestra">
                <x-app-icon name="x" class="w-5 h-5" />
            </button>

            <div class="text-center mb-6 mt-4">
                <h3 class="font-['Cinzel'] text-xl font-bold text-white mb-1">Unisciti a una Campagna</h3>
                <p class="text-xs text-slate-400">Incolla il codice di invito ricevuto dal Game Master.</p>
            </div>

            <form method="POST" action="{{ route('campaigns.join') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Codice Invito</label>
                    <input type="text" name="code" required class="input-premium font-mono tracking-wider text-center text-base" placeholder="es. aB39kL92mX1..." />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="joinModalOpen = false" class="btn-secondary w-1/2 py-2.5 text-xs"><x-icon-label icon="x" label="Annulla" /></button>
                    <button type="submit" class="btn-primary w-1/2 py-2.5 text-xs"><x-icon-label icon="star-plus" label="Unisciti" /></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
