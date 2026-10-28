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

        <div class="flex items-center gap-2.5">
            <button @click="joinModalOpen = true" class="btn-secondary text-sm">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Unisciti con Codice</span>
            </button>
        </div>
    </div>

    <!-- Campaign Cards Grid -->
    @if($campaigns->isEmpty())
        <div class="glass-panel p-10 sm:p-14 rounded-2xl text-center max-w-xl mx-auto my-8 border-dashed border-white/20">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3 class="font-['Cinzel'] text-xl font-bold text-white mb-2">Nessuna campagna attiva</h3>
            <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                Non fai ancora parte di nessuna campagna. Creane una nuova da Game Master oppure inserisci un codice invito fornito dal tuo gruppo.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('campaigns.create') }}" class="btn-primary w-full sm:w-auto">
                    <span>Crea la tua prima Campagna</span>
                </a>
                <button @click="joinModalOpen = true" class="btn-secondary w-full sm:w-auto">
                    <span>Inserisci Codice Invito</span>
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
                            <span>Apri Grimorio</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
         style="display: none;">
        <div @click.away="joinModalOpen = false" class="glass-panel p-6 sm:p-8 rounded-2xl max-w-md w-full relative">
            <button @click="joinModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
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
                    <button type="button" @click="joinModalOpen = false" class="btn-secondary w-1/2 py-2.5 text-xs">Annulla</button>
                    <button type="submit" class="btn-primary w-1/2 py-2.5 text-xs">Unisciti</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
