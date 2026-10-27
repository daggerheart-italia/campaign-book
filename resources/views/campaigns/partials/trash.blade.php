<div class="space-y-6">

    <!-- Maintenance Controls -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Compact Revisions -->
        <div class="glass-panel p-5 rounded-2xl relative">
            <div class="flex items-center gap-2.5 mb-2">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <h3 class="font-['Cinzel'] text-sm sm:text-base font-bold text-white">Compatta Revisioni</h3>
                    <p class="text-[11px] text-slate-400">Conserva le ultime 2 revisioni per ciascuna scheda e rimuove lo storico ridondante.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('campaigns.compactRevisions', $campaign) }}" onsubmit="return confirm('Vuoi compattare le revisioni storiche di tutte le pagine? Questa azione è irreversibile.')">
                @csrf
                <button type="submit" class="btn-secondary w-full text-xs py-2 mt-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Esegui Compattazione Revisioni</span>
                </button>
            </form>
        </div>

        <!-- Empty Trash -->
        <div class="glass-panel p-5 rounded-2xl relative">
            <div class="flex items-center gap-2.5 mb-2">
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <h3 class="font-['Cinzel'] text-sm sm:text-base font-bold text-white">Svuota Cestino</h3>
                    <p class="text-[11px] text-slate-400">Elimina definitivamente tutti gli elementi presenti nel cestino.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('campaigns.emptyTrash', $campaign) }}" onsubmit="return confirm('ATTENZIONE: Questa azione eliminerà per sempre tutti gli elementi nel cestino. Continuare?')">
                @csrf
                <button type="submit" class="btn-danger w-full text-xs py-2 mt-2 justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Svuota Definitivamente Cestino</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Trashed Items Lists -->
    <div class="glass-panel p-5 rounded-2xl">
        <h3 class="font-['Cinzel'] text-base font-bold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            <span>Elementi nel Cestino</span>
        </h3>

        @php
            $hasTrashed = $trashedWikiPages->isNotEmpty() || $trashedMapPins->isNotEmpty() || $trashedSessionNotes->isNotEmpty();
        @endphp

        @if(!$hasTrashed)
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <div class="text-sm font-semibold text-slate-300">Il cestino è vuoto</div>
                <div class="text-xs text-slate-500 mt-1">Nessun elemento eliminato di recente.</div>
            </div>
        @else
            <!-- Trashed Wiki Pages -->
            @if($trashedWikiPages->isNotEmpty())
                <div class="mb-5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-400 mb-2">Pagine Wiki ({{ $trashedWikiPages->count() }})</h4>
                    <div class="divide-y divide-white/[0.05]">
                        @foreach($trashedWikiPages as $page)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $page->title }}</div>
                                    <div class="text-[10px] text-slate-400">Eliminata il {{ $page->deleted_at->format('d/m/Y H:i') }} • Categoria: {{ $page->category }}</div>
                                </div>
                                <form method="POST" action="{{ route('wiki.restore', [$campaign, $page->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !py-1 !px-2.5 text-xs text-emerald-400 hover:text-emerald-300">
                                        Ripristina
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Trashed Map Pins -->
            @if($trashedMapPins->isNotEmpty())
                <div class="mb-5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-rose-400 mb-2">Punti Mappa ({{ $trashedMapPins->count() }})</h4>
                    <div class="divide-y divide-white/[0.05]">
                        @foreach($trashedMapPins as $pin)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $pin->title }}</div>
                                    <div class="text-[10px] text-slate-400">Eliminato il {{ $pin->deleted_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <form method="POST" action="{{ route('pins.restore', [$campaign, $pin->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !py-1 !px-2.5 text-xs text-emerald-400 hover:text-emerald-300">
                                        Ripristina
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Trashed Session Notes -->
            @if($trashedSessionNotes->isNotEmpty())
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-indigo-400 mb-2">Note di Sessione ({{ $trashedSessionNotes->count() }})</h4>
                    <div class="divide-y divide-white/[0.05]">
                        @foreach($trashedSessionNotes as $note)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $note->title }}</div>
                                    <div class="text-[10px] text-slate-400">Eliminata il {{ $note->deleted_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <form method="POST" action="{{ route('notes.restore', [$campaign, $note->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !py-1 !px-2.5 text-xs text-emerald-400 hover:text-emerald-300">
                                        Ripristina
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>
