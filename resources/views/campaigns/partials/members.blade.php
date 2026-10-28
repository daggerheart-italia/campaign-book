<div class="space-y-6" x-data="{ 
    copyFeedback: '',
    copyToClipboard(text, label) {
        navigator.clipboard.writeText(text).then(() => {
            this.copyFeedback = label;
            setTimeout(() => { this.copyFeedback = ''; }, 2500);
        });
    }
}">

    <!-- Notification when link is copied -->
    <div x-show="copyFeedback" x-transition class="p-3 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-200 text-xs font-semibold flex items-center gap-2" x-cloak>
        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="'Copiato negli appunti: ' + copyFeedback"></span>
    </div>

    <!-- Invites Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Player Invite -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                <h3 class="font-['Cinzel'] text-base font-bold text-white">Invito per Giocatori</h3>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed mb-4">
                Condividi questo link con i giocatori. Potranno visualizzare il grimorio, inserire annotazioni e aggiornare schede.
            </p>

            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ url('/join/' . $campaign->invite_code_player) }}" 
                           class="input-premium text-xs font-mono select-all bg-black/40">
                    <button @click="copyToClipboard('{{ url('/join/' . $campaign->invite_code_player) }}', 'Link Giocatore')" 
                            class="btn-secondary text-xs py-2 px-3 shrink-0" title="Copia Link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    </button>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>Codice rapido: <strong class="text-indigo-300 font-mono">{{ $campaign->invite_code_player }}</strong></span>
                </div>
            </div>
        </div>

        <!-- GM Invite -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <h3 class="font-['Cinzel'] text-base font-bold text-white">Invito per Co-Game Master</h3>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed mb-4">
                Condividi questo link con chi deve avere pieni privilegi di gestione, svuotamento del cestino e compattazione revisioni.
            </p>

            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ url('/join/' . $campaign->invite_code_gm) }}" 
                           class="input-premium text-xs font-mono select-all bg-black/40">
                    <button @click="copyToClipboard('{{ url('/join/' . $campaign->invite_code_gm) }}', 'Link Game Master')" 
                            class="btn-secondary text-xs py-2 px-3 shrink-0" title="Copia Link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    </button>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>Codice rapido: <strong class="text-amber-300 font-mono">{{ $campaign->invite_code_gm }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Members Roster -->
    <div class="glass-panel p-5 rounded-2xl">
        <h3 class="font-['Cinzel'] text-base font-bold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Partecipanti alla Campagna ({{ $campaign->users->count() }})</span>
        </h3>

        <div class="divide-y divide-white/[0.06]">
            @foreach($campaign->users as $member)
                @php
                    $isMemberGm = ($member->pivot->role === 'gm' || $member->id === $campaign->owner_id);
                    $isOwner = ($member->id === $campaign->owner_id);
                @endphp
                <div class="py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/20 to-purple-500/20 border border-white/10 flex items-center justify-center font-bold text-amber-300 text-sm shrink-0">
                            {{ substr($member->name, 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-white">{{ $member->name }}</span>
                                @if($isOwner)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">Creatore</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400">{{ $member->email }}</div>
                        </div>
                    </div>

                    <!-- Role & Management Controls -->
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        @if($isGm && !$isOwner)
                            <!-- Role change form -->
                            <form method="POST" action="{{ route('campaigns.updateMemberRole', [$campaign, $member->id]) }}" class="inline">
                                @csrf
                                <select name="role" onchange="this.form.submit()" class="input-premium text-xs py-1 px-2.5 w-auto">
                                    <option value="player" {{ $member->pivot->role === 'player' ? 'selected' : '' }}>Giocatore</option>
                                    <option value="gm" {{ $member->pivot->role === 'gm' ? 'selected' : '' }}>Game Master</option>
                                </select>
                            </form>

                            <!-- Remove member form -->
                            <form method="POST" action="{{ route('campaigns.removeMember', [$campaign, $member->id]) }}" onsubmit="return confirm('Rimuovere questo membro dalla campagna?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10" title="Rimuovi membro">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $isMemberGm ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isMemberGm ? 'bg-amber-400' : 'bg-indigo-400' }}"></span>
                                {{ $isMemberGm ? 'Game Master' : 'Giocatore' }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
