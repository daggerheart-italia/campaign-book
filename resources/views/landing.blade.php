@extends('layouts.app')

@section('title', 'Daggerheart Campaign Book | Il tuo compendio di campagna')

@section('content')
<div class="relative py-8 md:py-16">
    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto px-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-semibold mb-6 tracking-wide uppercase">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            Daggerheart RPG Campaign Manager
        </div>

        <h1 class="font-['Cinzel'] text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight">
            Forgia la tua leggenda in <span class="bg-gradient-to-r from-amber-400 via-amber-200 to-amber-500 bg-clip-text text-transparent">Daggerheart</span>
        </h1>

        <p class="text-base sm:text-lg text-slate-300 mb-8 max-w-2xl mx-auto leading-relaxed">
            Il compendio digitale definitivo per Game Master e Giocatori. Wiki condivisa con revisioni sicure, mappa interattiva con punti di interesse e diario di sessione integrato.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 max-w-md mx-auto mb-16">
            <a href="{{ route('register') }}" class="btn-primary w-full sm:w-auto py-3 px-6 text-base">
                <x-icon-label icon="arrow-right" label="Inizia Subito Gratis" />
            </a>
            <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto py-3 px-6 text-base">
                <x-icon-label icon="log-in" label="Accedi al tuo Grimorio" />
            </a>
        </div>
    </div>

    <!-- Feature Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto px-4">
        <!-- Feature 1 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-4">
                <x-app-icon name="book-open" class="w-6 h-6" />
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Wiki & Cronologia Revisioni</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Tutti i partecipanti possono creare e modificare schede lore e PNG. Grazie alla cronologia delle revisioni e al cestino gestito dai GM, nessun dato andrà mai perso.
            </p>
        </div>

        <!-- Feature 2 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-4">
                <x-app-icon name="map" class="w-6 h-6" />
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Mappa Tattica & Punti d'Interesse</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Esplora il mondo di gioco posizionando pin trascinabili collegati direttamente agli articoli della wiki. Ideale su smartphone e tablet.
            </p>
        </div>

        <!-- Feature 3 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-4">
                <x-app-icon name="pencil" class="w-6 h-6" />
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Diario di Sessione & Collaborazione</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Prendi appunti durante i combattimenti, fissa le scoperte cruciali e invita amici con codici e link di accesso istantanei per Giocatori e GM.
            </p>
        </div>
    </div>
</div>
@endsection
