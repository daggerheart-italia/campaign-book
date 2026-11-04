@extends('layouts.app')

@section('title', 'Imposta nuova password')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-14">
    <div class="glass-panel p-6 sm:p-8 rounded-2xl relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-6">
            <h1 class="font-['Cinzel'] text-2xl sm:text-3xl font-bold text-white mb-2">Nuova Password</h1>
            <p class="text-sm text-slate-400">Imposta una nuova password per il tuo account.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required class="input-premium" />
                @error('email')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Nuova password</label>
                <input type="password" id="password" name="password" required class="input-premium" placeholder="Minimo 8 caratteri" />
                @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Conferma password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required class="input-premium" placeholder="Ripeti la password" />
            </div>

            <button type="submit" class="btn-primary w-full py-3 mt-2 text-base">
                <span>Reimposta password</span>
            </button>
        </form>
    </div>
</div>
@endsection
