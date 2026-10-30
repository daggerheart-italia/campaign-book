<header class="sticky top-0 z-40 w-full backdrop-blur-xl bg-[#090c15]/80 border-b border-white/[0.08]" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <!-- Brand / Logo -->
            <a href="{{ Auth::check() ? route('campaigns.index') : route('landing') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-600 to-amber-400 p-[1px] shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform">
                    <div class="w-full h-full bg-[#0d121f] rounded-[11px] flex items-center justify-center overflow-hidden">
                        <img src="/images/icon-192.png" alt="" class="w-6 h-6 object-contain">
                    </div>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="font-['Cinzel'] font-bold text-lg tracking-wide text-white group-hover:text-amber-400 transition-colors">CAMPAIGN BOOK</span>
                        <span class="rounded-md border border-amber-400/25 bg-amber-400/10 px-1.5 py-0.5 text-[10px] font-semibold tracking-normal text-amber-300">{{ config('app.version') }}</span>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider text-amber-500/80 font-medium -mt-1">Daggerheart Italia</span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            @auth
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{ route('campaigns.index') }}" title="Tutte le Campagne" 
                       class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('campaigns.index') ? 'bg-white/10 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Campagne
                    </a>

                    <a href="{{ route('campaigns.create') }}" title="Nuova Campagna" class="btn-primary text-xs py-1.5 px-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <!-- <span>Nuova Campagna</span> -->
                    </a>

                    <div class="h-5 w-px bg-white/10 mx-1"></div>

                    <!-- User Profile / Logout -->
                    <div class="flex items-center gap-3" x-data="{ open: false }">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500/30 to-purple-500/30 border border-white/20 flex items-center justify-center text-xs font-bold text-amber-300 uppercase">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </div>
                            <span class="text-sm font-medium text-slate-200">{{ Auth::user()->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors" title="Disconnetti">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center md:hidden gap-2">
                    <a href="{{ route('campaigns.create') }}" class="btn-primary !p-2 !rounded-lg text-xs" title="Nuova Campagna">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </a>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!mobileMenuOpen">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="mobileMenuOpen" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn-secondary text-xs py-1.5 px-3">Accedi</a>
                    <a href="{{ route('register') }}" class="btn-primary text-xs py-1.5 px-3">Registrati</a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    @auth
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden glass-panel border-x-0 border-t border-b border-white/10 px-4 py-4 space-y-3"
             x-cloak>
            <div class="flex items-center gap-3 pb-3 border-b border-white/10">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500/30 to-purple-500/30 border border-white/20 flex items-center justify-center font-bold text-amber-300 text-sm">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-white">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-slate-400">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="space-y-1">
                <a href="{{ route('campaigns.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-white/10">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Tutte le Campagne</span>
                </a>
                <a href="{{ route('campaigns.create') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-amber-300 hover:bg-amber-500/10">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Crea Nuova Campagna</span>
                </a>
            </div>

            <div class="pt-2 border-t border-white/10">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-300 hover:bg-rose-500/10">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Disconnetti</span>
                    </button>
                </form>
            </div>
        </div>
    @endauth
</header>
