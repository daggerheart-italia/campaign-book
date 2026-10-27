@extends('layouts.app')

@section('title', 'Registrazione')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-14">
    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <!-- Accent Glow -->
        <div class="absolute -top-12 -left-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-6">
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-2">Crea il tuo Profilo</h1>
            <p class="text-sm text-slate-400">Unisci le tue forze con il gruppo e inizia a giocare.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Nome o Nickname</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="input-premium" placeholder="es. Eldrin, Master Marco" />
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="input-premium" placeholder="nome@esempio.it" />
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="input-premium" placeholder="Almeno 8 caratteri" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Conferma Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                       class="input-premium" placeholder="Ripeti la password" />
            </div>

            <button type="submit" class="btn-primary w-full py-3 mt-2 text-base">
                <span>Crea Account e Inizia</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-center">
            <p class="text-xs text-slate-400">
                Hai già un account? 
                <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline underline-offset-2">Accedi qui</a>
            </p>
        </div>
    </div>
</div>
@endsection
