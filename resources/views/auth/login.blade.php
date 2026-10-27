@extends('layouts.app')

@section('title', 'Accedi')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-14">
    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <!-- Accent Glow -->
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-6">
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-2">Accedi al Grimorio</h1>
            <p class="text-sm text-slate-400">Bentornato, avventuriero. Inserisci le tue credenziali.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="input-premium" placeholder="nome@esempio.it" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
                </div>
                <input type="password" id="password" name="password" required
                       class="input-premium" placeholder="••••••••" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-900 border-white/20 text-amber-500 focus:ring-amber-500/20 focus:ring-offset-0">
                    <span class="text-xs text-slate-300">Rimani connesso</span>
                </label>
            </div>

            <button type="submit" class="btn-primary w-full py-3 mt-2 text-base">
                <span>Entra nella Campagna</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-center">
            <p class="text-xs text-slate-400">
                Non hai ancora un account? 
                <a href="{{ route('register') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline underline-offset-2">Registrati ora</a>
            </p>
        </div>
    </div>
</div>
@endsection
