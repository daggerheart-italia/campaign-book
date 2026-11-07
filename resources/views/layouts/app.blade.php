<!DOCTYPE html>
<html lang="it" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#090c15">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <title>@yield('title', 'Campaign Book') | Daggerheart Italia</title>

    <!-- Google Fonts: Outfit & Cinzel -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-[#090c15] text-slate-100 antialiased selection:bg-amber-500/30 selection:text-amber-200">
    <!-- Top Navigation Header -->
    @include('partials.nav')

    <!-- Flash Notifications -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 w-full pt-4">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms class="mb-4 p-4 rounded-xl glass-panel border-emerald-500/30 bg-emerald-950/40 text-emerald-200 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <x-app-icon name="circle-check" class="w-5 h-5 text-emerald-400 shrink-0" />
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-200 p-1" aria-label="Chiudi notifica">
                    <x-app-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms class="mb-4 p-4 rounded-xl glass-panel border-rose-500/30 bg-rose-950/40 text-rose-200 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <x-app-icon name="circle-x" class="w-5 h-5 text-rose-400 shrink-0" />
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-rose-400 hover:text-rose-200 p-1" aria-label="Chiudi notifica">
                    <x-app-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div x-data="{ show: true }" x-show="show" class="mb-4 p-4 rounded-xl glass-panel border-rose-500/30 bg-rose-950/40 text-rose-200">
                <div class="flex items-center gap-2 mb-2 text-rose-400 font-semibold text-sm">
                    <x-app-icon name="triangle-alert" class="w-5 h-5" />
                    <span>Si sono verificati degli errori:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 text-rose-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-3 sm:px-6 py-4 pb-24 md:pb-8">
        @yield('content')
    </main>

    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>
