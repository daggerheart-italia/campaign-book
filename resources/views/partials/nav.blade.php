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
                        <x-icon-label icon="archive" label="Campagne" />
                    </a>

                    <a href="{{ route('campaigns.create') }}" class="btn-primary text-xs py-1.5 px-3">
                        <x-icon-label icon="plus" label="Nuova Campagna" />
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
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors">
                                <x-icon-label icon="log-out" label="Disconnetti" />
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center md:hidden gap-2">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10" :aria-label="mobileMenuOpen ? 'Chiudi menu' : 'Apri menu'" data-hide-mobile-tooltip>
                        <x-app-icon name="menu" class="w-6 h-6" x-show="!mobileMenuOpen" />
                        <x-app-icon name="x" class="w-6 h-6" x-show="mobileMenuOpen" x-cloak />
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn-secondary text-xs py-1.5 px-3"><x-icon-label icon="log-in" label="Accedi" /></a>
                    <a href="{{ route('register') }}" class="btn-primary text-xs py-1.5 px-3"><x-icon-label icon="user-round-plus" label="Registrati" /></a>
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
                    <x-icon-label icon="archive" label="Tutte le Campagne" showOnMobile="true" />
                </a>
                <a href="{{ route('campaigns.create') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-amber-300 hover:bg-amber-500/10">
                    <x-icon-label icon="plus" label="Crea Nuova Campagna" showOnMobile="true" />
                </a>
            </div>

            <div class="pt-2 border-t border-white/10">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-300 hover:bg-rose-500/10">
                        <x-icon-label icon="log-out" label="Disconnetti" showOnMobile="true" />
                    </button>
                </form>
            </div>
        </div>
    @endauth
</header>
