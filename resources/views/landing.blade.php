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
                <span>Inizia Subito Gratis</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto py-3 px-6 text-base">
                <span>Accedi al tuo Grimorio</span>
            </a>
        </div>
    </div>

    <!-- Feature Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto px-4">
        <!-- Feature 1 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Wiki & Cronologia Revisioni</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Tutti i partecipanti possono creare e modificare schede lore e PNG. Grazie alla cronologia delle revisioni e al cestino gestito dai GM, nessun dato andrà mai perso.
            </p>
        </div>

        <!-- Feature 2 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Mappa Tattica & Punti d'Interesse</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Esplora il mondo di gioco posizionando pin trascinabili collegati direttamente agli articoli della wiki. Ideale su smartphone e tablet.
            </p>
        </div>

        <!-- Feature 3 -->
        <div class="glass-panel glass-panel-hover p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-2">Diario di Sessione & Collaborazione</h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Prendi appunti durante i combattimenti, fissa le scoperte cruciali e invita amici con codici e link di accesso istantanei per Giocatori e GM.
            </p>
        </div>
    </div>
</div>
@endsection
