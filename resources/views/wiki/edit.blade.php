@extends('layouts.app')

@section('title', 'Modifica: ' . $wiki->title . ' - ' . $campaign->name)

@section('content')
@php
    $lockConfig = [
        'acquireUrl' => route('wiki.edit.lock', [$campaign, $wiki]),
        'heartbeatUrl' => route('wiki.edit.heartbeat', [$campaign, $wiki]),
        'releaseUrl' => route('wiki.edit.release', [$campaign, $wiki]),
        'stateUrl' => route('wiki.edit.state', [$campaign, $wiki]),
        'joinQueueUrl' => route('wiki.edit.queue.join', [$campaign, $wiki]),
        'leaveQueueUrl' => route('wiki.edit.queue.leave', [$campaign, $wiki]),
        'acceptOfferUrl' => route('wiki.edit.queue.accept', [$campaign, $wiki]),
        'advanceUrl' => route('wiki.edit.queue.advance', [$campaign, $wiki]),
        'forceReleaseUrl' => route('wiki.edit.forceRelease', [$campaign, $wiki]),
        'csrfToken' => csrf_token(),
        'campaignId' => $campaign->id,
        'wikiId' => $wiki->id,
        'userId' => auth()->id(),
    ];
@endphp
<div class="max-w-4xl mx-auto my-4 sm:my-8"
     x-data='wikiEditLock(@json($lockConfig))'
     x-init="init()"
     x-on:submit="if (!canEdit) $event.preventDefault()">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-white transition-colors">{{ $campaign->name }}</a>
        <span>/</span>
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'tab' => 'wiki']) }}" class="hover:text-white transition-colors">Wiki</a>
        <span>/</span>
        <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="hover:text-white transition-colors">{{ $wiki->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-medium">Modifica</span>
    </div>

    <div class="glass-panel p-5 sm:p-8 rounded-2xl">
        <div class="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-white/[0.08]">
            <div>
                <h1 class="font-['Cinzel'] text-xl sm:text-2xl font-bold text-white">Modifica Scheda</h1>
                <p class="text-xs text-slate-400 mt-1">Ogni modifica genera una nuova revisione sicura consultabile in cronologia.</p>
                @if($restoreRevision)
                    <p class="text-xs text-amber-300 mt-2">Stai preparando il ripristino della revisione del {{ $restoreRevision->created_at->format('d/m/Y H:i') }}. Puoi modificare il testo prima di salvarlo.</p>
                @endif
            </div>
            <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-1.5 px-3">Annulla</a>
        </div>

        <section class="mb-5 rounded-xl border border-white/10 bg-black/20 p-4" aria-live="polite">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100" x-text="statusMessage"></p>
                    <p class="mt-1 text-xs text-slate-400" x-show="owner" x-text="owner ? 'In modifica: ' + owner.name : ''" x-cloak></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-primary text-xs py-2 px-3" x-show="!canEdit && !offerAvailable && !queuePosition && !sameUserOtherTab" @click="joinQueue()" x-cloak>
                        Mettimi in coda
                    </button>
                    <button type="button" class="btn-primary text-xs py-2 px-3" x-show="offerAvailable" @click="acceptOffer()" x-cloak>
                        Accetta il turno
                    </button>
                    <button type="button" class="btn-secondary text-xs py-2 px-3" x-show="queuePosition" @click="leaveQueue()" x-cloak>
                        Esci dalla coda
                    </button>
                    <button type="button" class="btn-secondary text-xs py-2 px-3" x-show="sameUserOtherTab" @click="acquire(true)" x-cloak>
                        Usa questa scheda
                    </button>
                    @if($campaign->isGm(auth()->user()))
                        <button type="button" class="btn-danger text-xs py-2 px-3" @click="forceRelease()" x-show="owner && !canEdit" x-cloak>
                            Forza rilascio
                        </button>
                    @endif
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400" x-show="queuePosition" x-text="queuePosition ? 'La tua posizione in coda: ' + queuePosition : ''" x-cloak></p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-400" x-show="queue.length > 0" x-cloak>
                <span>In coda:</span>
                <template x-for="entry in queue" :key="entry.position">
                    <span><span x-text="entry.position + '. '"></span><span x-text="entry.name"></span></span>
                </template>
            </div>
            <p class="mt-2 text-xs text-rose-300" x-show="errorMessage" x-text="errorMessage" role="alert" x-cloak></p>
            <p class="mt-2 text-xs text-amber-300" x-show="!canEdit && hasDraft" x-cloak>Il testo presente nell’editor non viene cancellato. Riacquisisci il turno prima di salvarlo.</p>
        </section>

        <form method="POST" action="{{ route('wiki.update', [$campaign, $wiki]) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab_id" x-model="tabId">
            <input type="hidden" name="lock_token" x-model="lockToken">
            @if($restoreRevision)
                <input type="hidden" name="restore_revision_id" value="{{ $restoreRevision->id }}">
            @endif

            <fieldset :disabled="!canEdit" class="space-y-5 disabled:opacity-70">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Titolo della Scheda *</label>
                    <input type="text" name="title" value="{{ old('title', $restoreRevision?->title ?? $wiki->title) }}" required
                           class="input-premium text-base font-semibold" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Categoria</label>
                    <select name="category" class="input-premium text-sm">
                        @foreach($categoryOptions as $cat)
                            <option value="{{ $cat }}" {{ old('category', $restoreRevision?->category ?? $wiki->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="parent_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Scheda genitore</label>
                <select id="parent_id" name="parent_id" class="input-premium text-sm">
                    <option value="">Nessun genitore</option>
                    @foreach($parentPages as $parentPage)
                        <option value="{{ $parentPage->id }}" {{ (string) old('parent_id', $wiki->parent_id) === (string) $parentPage->id ? 'selected' : '' }}>
                            {{ $parentPage->category ?: 'Generale' }} — {{ $parentPage->title }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content & Preview -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Contenuto *</label>
                <x-markdown-editor :campaign="$campaign" :value="old('content', $restoreRevision?->content ?? $wiki->content)" min-height="22rem"
                                   placeholder="Scrivi qui la descrizione. Digita [[ per collegare un'altra pagina della wiki." />
                <p class="mt-1 text-[11px] text-slate-500">Salvato come Markdown. Usa [[Titolo pagina]] per creare link tra le schede.</p>
            </div>

            <!-- Summary of edits -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Motivo o Sommario della Revisione (opzionale)</label>
                <input type="text" name="summary" value="{{ old('summary') }}" 
                       class="input-premium text-xs" placeholder="es. Aggiunto segreto sulla stirpe elfica o modificati punti ferita" />
            </div>

            <div class="pt-4 border-t border-white/[0.08] flex items-center justify-end gap-3">
                <a href="{{ route('wiki.show', [$campaign, $wiki]) }}" class="btn-secondary text-xs py-2 px-4">Annulla</a>
                <button type="submit" class="btn-primary text-xs py-2 px-5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Salva Nuova Revisione</span>
                </button>
            </div>
            </fieldset>
        </form>
    </div>
</div>

<script>
function wikiEditLock(config) {
    return {
        tabId: '',
        lockToken: '',
        canEdit: false,
        owner: null,
        queue: [],
        queuePosition: null,
        offerAvailable: false,
        sameUserOtherTab: false,
        errorMessage: '',
        statusMessage: 'Verifica del turno di modifica…',
        polling: false,
        hasDraft: false,
        init() {
            const storageKey = `wiki-edit-tab:${config.campaignId}:${config.wikiId}`;
            this.tabId = sessionStorage.getItem(storageKey) || crypto.randomUUID();
            sessionStorage.setItem(storageKey, this.tabId);
            this.hasDraft = Boolean(document.querySelector('[name="content"]'));
            this.acquire();
            this.pollTimer = window.setInterval(() => this.refreshState(), 10000);
            this.heartbeatTimer = window.setInterval(() => this.heartbeat(), 30000);
            this.releaseOnExit = () => this.release(true);
            window.addEventListener('pagehide', this.releaseOnExit);
        },
        destroy() {
            window.clearInterval(this.pollTimer);
            window.clearInterval(this.heartbeatTimer);
            window.removeEventListener('pagehide', this.releaseOnExit);
        },
        async request(url, method = 'POST', payload = null, keepalive = false) {
            const options = {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                keepalive,
            };
            if (payload !== null) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(payload);
            }
            const response = await fetch(url, options);
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || `Richiesta non riuscita (${response.status}).`);
            }
            return data;
        },
        async acquire(takeover = false) {
            try {
                const result = await this.request(config.acquireUrl, 'POST', { tab_id: this.tabId, takeover });
                this.errorMessage = '';
                this.canEdit = result.status === 'acquired';
                this.lockToken = this.canEdit ? result.lock_token : '';
                this.owner = result.owner || null;
                this.sameUserOtherTab = result.status === 'same_user_other_tab';
                this.statusMessage = this.canEdit
                    ? 'Hai il turno di modifica.'
                    : this.sameUserOtherTab
                        ? 'Il turno è già attivo in un’altra tua scheda.'
                        : result.status === 'queued'
                            ? 'Sei in attesa del turno di modifica.'
                            : 'Un altro utente sta modificando questa scheda.';
                await this.refreshState();
            } catch (error) {
                this.errorMessage = error.message;
                this.statusMessage = 'Impossibile acquisire il turno.';
            }
        },
        async refreshState() {
            if (this.polling) return;
            this.polling = true;
            try {
                await this.request(config.advanceUrl, 'POST', {});
                const state = await this.request(`${config.stateUrl}?tab_id=${encodeURIComponent(this.tabId)}`, 'GET');
                this.owner = state.owner;
                this.queue = state.queue;
                this.sameUserOtherTab = !this.canEdit && state.owner?.user_id === config.userId;
                this.offerAvailable = Boolean(state.offer?.available);
                const position = state.queue.find(entry => entry.is_current_user);
                this.queuePosition = position?.position || null;
                if (this.canEdit) {
                    this.statusMessage = 'Hai il turno di modifica.';
                } else if (this.offerAvailable) {
                    this.statusMessage = 'Il turno è disponibile per te: accettalo entro pochi secondi.';
                } else if (position) {
                    this.statusMessage = 'Sei in attesa del turno di modifica.';
                } else if (this.owner) {
                    this.statusMessage = 'Un altro utente sta modificando questa scheda.';
                } else {
                    this.statusMessage = 'Il turno è libero. Puoi acquisirlo.';
                }
                this.errorMessage = '';
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.polling = false;
            }
        },
        async heartbeat() {
            if (!this.canEdit || !this.lockToken) return;
            try {
                await this.request(config.heartbeatUrl, 'POST', { tab_id: this.tabId, lock_token: this.lockToken });
            } catch (error) {
                this.canEdit = false;
                this.lockToken = '';
                this.statusMessage = 'Il turno di modifica è scaduto o è stato trasferito.';
                this.errorMessage = error.message;
                this.refreshState();
            }
        },
        async joinQueue() {
            try {
                const result = await this.request(config.joinQueueUrl, 'POST', { tab_id: this.tabId });
                if (result.status === 'acquired') {
                    this.canEdit = true;
                    this.lockToken = result.lock_token;
                }
                await this.refreshState();
            } catch (error) {
                this.errorMessage = error.message;
            }
        },
        async leaveQueue() {
            try {
                await this.request(config.leaveQueueUrl, 'POST', {});
                await this.refreshState();
            } catch (error) {
                this.errorMessage = error.message;
            }
        },
        async acceptOffer() {
            try {
                const result = await this.request(config.acceptOfferUrl, 'POST', { tab_id: this.tabId });
                this.lockToken = result.lock_token;
                this.canEdit = true;
                this.offerAvailable = false;
                this.errorMessage = '';
                this.statusMessage = 'Hai accettato il turno di modifica.';
            } catch (error) {
                this.errorMessage = error.message;
                await this.refreshState();
            }
        },
        async forceRelease() {
            if (!window.confirm('Vuoi forzare il rilascio del turno di modifica?')) return;
            try {
                await this.request(config.forceReleaseUrl, 'POST', { confirm: true });
                await this.refreshState();
            } catch (error) {
                this.errorMessage = error.message;
            }
        },
        async release(keepalive = false) {
            if (!this.canEdit || !this.lockToken) return;
            try {
                await this.request(config.releaseUrl, 'POST', {
                    tab_id: this.tabId,
                    lock_token: this.lockToken,
                }, keepalive);
            } catch (error) {
                if (!keepalive) this.errorMessage = error.message;
            }
        },
    };
}
</script>
@endsection
