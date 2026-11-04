@extends('layouts.app')

@section('title', 'Recupera password')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-14">
    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-6">
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-2">Recupera Password</h1>
            <p class="text-sm text-slate-400">Inserisci la tua email per ricevere il link di reset.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="input-premium" placeholder="nome@esempio.it" />
                @error('email')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full py-3 mt-2 text-base">
                <span>Invia link di reset</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-center">
            <p class="text-xs text-slate-400">
                <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline underline-offset-2">Torna al login</a>
            </p>
        </div>
    </div>
</div>
@endsection
