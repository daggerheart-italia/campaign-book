<!DOCTYPE html>
<html lang="it" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#090c15">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <title>@yield('title', 'Campaign Book') | Daggerheart Italia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-[#090c15] text-slate-100 antialiased selection:bg-amber-500/30 selection:text-amber-200">
    <header class="border-b border-white/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('public.show', $campaign->public_slug) }}" class="font-['Cinzel'] font-bold text-amber-400 truncate">{{ $campaign->name }}</a>
            <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-500/15 text-slate-300 border border-slate-500/30">Sola lettura</span>
        </div>
    </header>

    <main class="flex-1 w-full max-w-7xl mx-auto px-3 sm:px-6 py-6">
        @yield('content')
    </main>

    <footer class="py-4 text-center text-xs text-slate-500">Campaign Book · Daggerheart Italia</footer>
    @stack('scripts')
</body>
</html>
