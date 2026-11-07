<div x-data="customImageMap({
    campaignId: {{ $campaign->id }},
    activeMapId: {{ $activeMap ? $activeMap->id : 'null' }},
    maps: {{ Js::from($campaign->maps->map(fn($m) => [
        'id' => $m->id,
        'title' => $m->title,
        'image_url' => $m->image_url,
        'is_default' => $m->is_default,
        'pins_count' => $m->pins_count ?? $m->pins()->count(),
    ])) }},
    fallbackMapUrl: '{{ asset('images/sample-fantasy-map.svg') }}',
    initialPins: {{ Js::from($campaign->mapPins->map(fn($p) => [
        'id' => $p->id,
        'campaign_map_id' => $p->campaign_map_id,
        'title' => $p->title,
        'description' => $p->description,
        'x' => (float)$p->x,
        'y' => (float)$p->y,
        'color' => $p->color ?: '#e11d48',
        'locked' => (bool)$p->locked,
        'wiki_page_id' => $p->wiki_page_id,
        'wiki_page_title' => $p->wikiPage ? $p->wikiPage->title : null,
        'wiki_page_url' => $p->wikiPage ? route('wiki.show', [$campaign, $p->wikiPage]) : null,
        'sync_version' => $p->sync_version,
    ])) }},
    storePinUrl: '{{ route('pins.store', $campaign) }}',
    updatePinBaseUrl: '{{ url("/campaigns/{$campaign->id}/pins") }}',
    storeMapUrl: '{{ route('maps.store', $campaign) }}',
    storeMapChunkUrl: '{{ route('maps.storeChunk', $campaign) }}',
    mapChunkSize: {{ \App\Http\Controllers\CampaignMapController::MAX_CHUNK_BYTES }},
    mapMaxBytes: {{ \App\Http\Controllers\CampaignMapController::MAX_IMAGE_BYTES }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-4" data-parent-page-select>

    <div class="glass-panel relative z-30 flex items-center gap-2 rounded-xl p-2 sm:p-3">
        <template x-if="maps.length === 0">
            <div class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 text-xs font-semibold text-amber-300">
                <x-app-icon name="map" class="h-4 w-4 shrink-0" />
                <span>Mappa Predefinita (Esempio)</span>
            </div>
        </template>
        <template x-if="maps.length > 0">
            <x-map-selector />
        </template>

        <button type="button" @click="uploadModalOpen = true" aria-label="Carica mappa" title="Carica mappa"
                class="btn-primary !h-11 !w-11 !shrink-0 !p-0">
            <x-app-icon name="upload" />
        </button>

        @if($isGm)
            <template x-if="activeMap">
                <details class="relative shrink-0" @click.outside="$el.open = false" @keydown.escape.stop="$el.open = false; $el.querySelector('summary').focus()">
                    <summary aria-label="Gestisci mappa" title="Gestisci mappa"
                             class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-lg border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 [&::-webkit-details-marker]:hidden">
                        <x-app-icon name="ellipsis-vertical" />
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-white/10 bg-[#151b27] p-1.5 shadow-2xl">
                        <template x-if="!activeMap.is_default">
                            <form :action="'/campaigns/{{ $campaign->id }}/maps/' + activeMap.id + '/default'" method="POST">
                                @csrf
                                <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 text-left text-xs text-amber-300 hover:bg-white/10">
                                    <x-app-icon name="check" />
                                    <span>Imposta come predefinita</span>
                                </button>
                            </form>
                        </template>
                        <form :action="'/campaigns/{{ $campaign->id }}/maps/' + activeMap.id" method="POST" onsubmit="return confirm('Eliminare questa mappa e i suoi segnaposto?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 text-left text-xs text-rose-400 hover:bg-white/10">
                                <x-app-icon name="trash-2" />
                                <span>Elimina mappa</span>
                            </button>
                        </form>
                    </div>
                </details>
            </template>
        @endif
    </div>

    <div class="glass-panel p-2 rounded-xl flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="if (activeMap) window.open('/campaigns/{{ $campaign->id }}/maps/' + activeMap.id + '/print', '_blank')" class="btn-secondary min-h-11 px-3 text-xs border">
                <x-icon-label icon="printer" label="Stampa mappa" />
            </button>
            <button @click="showLabels = !showLabels"
                    :class="showLabels ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 border-white/5'"
                    class="btn-secondary min-h-11 px-3 text-xs border">
                <x-icon-label icon="tag" label="Etichette" />
            </button>
        </div>

        <div class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-black/40 p-0.5"
             role="group"
             aria-label="Controlli zoom mappa">
            <button type="button" @click="zoomOut" :disabled="zoomLevel <= 50"
                    class="min-h-11 min-w-11 rounded px-2 text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                <x-icon-label icon="zoom-out" label="Riduci zoom" hideOnDesktop="true"/>
            </button>
            <span class="min-w-12 px-1 text-center text-xs font-mono text-slate-200" x-text="(Math.round(zoomLevel * 10) / 10) + '%'"></span>
            <button type="button" @click="zoomIn" :disabled="zoomLevel >= 300"
                    class="min-h-11 min-w-11 rounded px-2 text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                <x-icon-label icon="zoom-in" label="Aumenta zoom" hideOnDesktop="true"/>
            </button>
            <button type="button" @click="resetZoom" :disabled="zoomLevel === 100"
                    class="min-h-11 min-w-11 rounded px-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                <x-icon-label icon="expand" label="Adatta" hideOnDesktop="true"/>
            </button>
        </div>
    </div>

    <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-3 py-2 text-xs text-slate-300 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
        <span>Trascina per spostare la mappa • Pizzica per zoomare su mobile, Ctrl + rotella su desktop • Tasto destro o doppio clic per inserire un pin • Trascina un pin per riposizionarlo</span>
    </div>

    <!-- Interactive Map Stage (Custom Image with Pins) -->
    <div class="glass-panel p-2 sm:p-3 rounded-2xl relative">
        <div x-ref="mapViewport"
             @mousedown="startPan($event)"
             @wheel="handleMapWheel($event)"
             @touchstart="startMapTouch($event)"
             @touchmove.prevent="moveMapTouch($event)"
             @touchend="endMapTouch($event)"
             @touchcancel="endMapTouch($event)"
             :class="isPanning ? 'cursor-grabbing' : 'cursor-grab'"
             class="flex h-[70vh] min-h-80 max-h-[800px] w-full overflow-auto rounded-xl border border-white/10 bg-[#070a12] scrollbar-thin select-none touch-none">
            <div class="relative m-auto shrink-0 transition-none"
                 :style="mapCanvasStyle">

                <!-- Map Image -->
                <img x-ref="mapImage" :src="currentMapUrl"
                     @load="fitMap"
                     @contextmenu.prevent="handleMapInsert($event)"
                     @dblclick.stop="handleMapInsert($event)"
                     alt="Mappa di Campagna"
                     class="pointer-events-auto block h-full w-full rounded-lg object-fill shadow-2xl"
                     draggable="false" />

                <!-- Clickable Pins Overlay -->
                <template x-for="(pin, pinIndex) in currentPins" :key="pin.id">
                    <div data-map-pin
                         class="absolute -translate-x-1/2 -translate-y-full cursor-pointer group z-30 transition-transform duration-75"
                         :style="'left: ' + pin.x + '%; top: ' + pin.y + '%;'"
                         @mousedown="startDrag($event, pin)"
                         @touchstart="startTouchDrag($event, pin)"
                         @click.stop="openPinPopover(pin, $event)"
                         @dblclick.stop
                         :class="pin.locked ? 'cursor-not-allowed' : 'cursor-grab'">
                        
                        <!-- Visible Label directly over/below pin -->
                        <div x-show="showLabels"
                             class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-0.5 rounded-md bg-black/85 backdrop-blur-md border border-white/15 text-[11px] font-bold text-white shadow-lg whitespace-nowrap pointer-events-none tracking-wide">
                            <span x-text="pin.title"></span>
                        </div>

                        <!-- Pin Marker Icon -->
                        <div class="relative flex flex-col items-center">
                            <div class="h-5 w-5 rounded-full flex items-center justify-center text-white border-2 border-white shadow-xl transition-transform group-hover:scale-125 text-[10px] font-bold"
                                 :style="'background-color: ' + pin.color + '; box-shadow: 0 0 12px ' + pin.color + ', 0 4px 8px rgba(0,0,0,0.6);'">
                                <template x-if="showLabels">
                                    <x-app-icon name="circle-dot" class="h-2.5 w-2.5" stroke-width="3" />
                                </template>
                                <template x-if="!showLabels">
                                    <span x-text="pinIndex + 1"></span>
                                </template>
                            </div>
                            <!-- Pin Needle Point -->
                            <div class="w-0 h-0 border-x-[3px] border-x-transparent border-t-[5px] -mt-[1px]"
                                 :style="'border-top-color: ' + pin.color + ';'"></div>
                        </div>

                    </div>
                </template>

            </div>
        </div>
    </div>

    <!-- Location Roster (List of Places on Current Map) -->
    <div class="glass-panel p-4 rounded-xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-['Cinzel'] text-sm font-bold text-white flex items-center gap-2">
                <x-app-icon name="map-pin" class="w-4 h-4 text-amber-400" />
                <span>Luoghi Segnati (<span x-text="currentPins.length"></span>)</span>
            </h3>
        </div>

        <template x-if="currentPins.length === 0">
            <p class="text-xs text-slate-400 text-center py-4">Nessun punto segnato su questa mappa. Clicca sull'immagine per posizionare il primo pin!</p>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5">
            <template x-for="(pin, pinIndex) in currentPins" :key="pin.id">
                <div class="bg-white/[0.03] hover:bg-white/[0.07] border border-white/[0.07] p-2.5 rounded-lg flex min-w-0 items-center justify-between gap-2 transition-colors">
                    <button @click="openPinPopover(pin)" class="flex min-h-11 items-center gap-2.5 text-left flex-1 min-w-0">
                        <span class="w-5 h-5 rounded-full shrink-0 shadow-sm flex items-center justify-center text-[10px] font-bold text-white" :style="'background-color: ' + pin.color" x-text="pinIndex + 1"></span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold text-slate-200 line-clamp-2 break-words" x-text="pin.title"></div>
                            <div class="text-[10px] text-slate-400 line-clamp-2 break-words" x-text="pin.description || 'Coordinate: ' + pin.x.toFixed(1) + '%, ' + pin.y.toFixed(1) + '%'"></div>
                        </div>
                    </button>

                    <div class="flex items-center gap-1 shrink-0">
                        <template x-if="pin.wiki_page_url">
                            <a :href="pin.wiki_page_url" aria-label="Apri Scheda Wiki" title="Apri Scheda Wiki" class="inline-flex min-h-11 min-w-11 items-center justify-center p-1 text-amber-400 hover:text-amber-300">
                                <x-app-icon name="book-open" class="w-3.5 h-3.5" />
                            </a>
                        </template>

                        <template x-if="!pin.wiki_page_url">
                            <a :href="'/campaigns/{{ $campaign->id }}/wiki/create?pin_id=' + pin.id + '&title=' + encodeURIComponent(pin.title) + '&category=' + encodeURIComponent('Luoghi & Città')"
                               aria-label="Crea Scheda Wiki" title="Crea Scheda Wiki" class="inline-flex min-h-11 min-w-11 items-center justify-center p-1 text-emerald-400 hover:text-emerald-300">
                                <x-app-icon name="plus" class="w-3.5 h-3.5" />
                            </a>
                        </template>

                        <button @click="deletePin(pin)" aria-label="Elimina Segnaposto" title="Elimina Segnaposto" class="inline-flex min-h-11 min-w-11 items-center justify-center p-1 text-slate-500 hover:text-rose-400">
                            <x-app-icon name="trash-2" class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Pin Modal -->
    <template x-teleport="body">
    <dialog x-ref="pinDialog" @cancel.prevent="modalOpen = false" @keydown.escape.stop.prevent="modalOpen = false" @click.self="modalOpen = false"
            aria-labelledby="pin-dialog-title"
            class="pin-dialog fixed inset-0 m-auto h-dvh max-h-dvh w-full max-w-none overflow-hidden border border-white/10 bg-[#121826] text-slate-200 shadow-2xl sm:h-auto sm:max-h-[90dvh] sm:max-w-md sm:rounded-2xl">
        <div class="flex h-full max-h-dvh flex-col sm:max-h-[90dvh]">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-3 sm:px-6 sm:pt-4">
                <h3 id="pin-dialog-title" class="min-w-0 break-words font-['Cinzel'] text-lg font-bold text-white"
                    x-text="modalMode === 'create' ? 'Nuovo Punto sulla Mappa' : (modalMode === 'edit' ? 'Modifica segnaposto' : selectedPin.title)"></h3>
                <button type="button" @click="modalOpen = false" class="flex min-h-11 min-w-11 shrink-0 items-center justify-center text-slate-400 hover:text-white" aria-label="Chiudi segnaposto" aria-label-hidden="true">
                    <x-app-icon name="x" class="w-5 h-5" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
            <template x-if="modalMode === 'create' || modalMode === 'edit'">
            <form id="pin-dialog-form" @submit.prevent="modalMode === 'create' ? saveNewPin() : savePinDetails()" class="space-y-4">
                <p x-show="modalMode === 'create'" class="text-xs text-slate-400 mb-4" x-text="'Posizione: X ' + newPinCoords.x.toFixed(1) + '% • Y ' + newPinCoords.y.toFixed(1) + '%'"></p>
                <div>
                    <label for="pin-dialog-name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Nome del Luogo o Etichetta *</label>
                    <input id="pin-dialog-name" type="text" x-model="pinForm.title" required maxlength="255" class="input-premium text-sm font-semibold" placeholder="es. Cittadella delle Ossa, Locanda del Lupo" />
                </div>

                <div>
                    <label for="pin-dialog-description" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Descrizione / Note rapide</label>
                    <textarea id="pin-dialog-description" x-model="pinForm.description" rows="4" class="input-premium text-xs" placeholder="Chi vive qui? Quali indizi o pericoli attendono gli avventurieri?"></textarea>
                </div>

                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Colore Segnaposto</span>
                    <div class="flex items-center gap-3 flex-wrap">
                        <template x-for="option in pinColors" :key="option.value">
                            <button type="button" @click="pinForm.color = option.value" :aria-label="option.label"
                                    :aria-pressed="pinForm.color === option.value" class="h-6 w-6 rounded-full border-2 transition-transform hover:scale-110"
                                    :class="pinForm.color === option.value ? 'border-white scale-110' : 'border-transparent'"
                                    :style="'background-color: ' + option.value"></button>
                        </template>
                    </div>
                </div>
                <x-parent-page-select :parent-pages="$campaign->wikiPages" name="pin_form_wiki_page_id"
                    label="Collega a Scheda Wiki" modelable="pinForm.wiki_page_id" />
                <p x-show="mapError" x-text="mapError" role="alert" class="text-xs text-rose-300"></p>

            </form>
            </template>

            <template x-if="modalMode === 'view'">
                <div>
                    <div class="flex items-center gap-2.5 mb-2">
                        <span class="w-4 h-4 rounded-full shrink-0" :style="'background-color: ' + selectedPin.color"></span>
                        <span class="min-w-0 break-words flex-1 text-sm font-semibold text-slate-200" x-text="selectedPin.title"></span>
                        <button type="button" @click="startPinEdit()" class="shrink-0 min-h-11 min-w-11 flex items-center justify-center text-slate-400 hover:text-amber-300">
                            <x-icon-label icon="pencil" label="Modifica nome e descrizione" />
                        </button>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed mb-4 whitespace-pre-wrap" x-text="selectedPin.description || 'Nessuna descrizione specificata.'"></p>
            <div class="space-y-3 pt-3 border-t border-white/10">
                <!-- If linked to Wiki page -->
                <template x-if="selectedPin.wiki_page_url">
                    <div class="space-y-2">
                        <a :href="selectedPin.wiki_page_url" class="btn-primary w-full text-xs min-h-11 justify-center shadow-lg">
                            <x-icon-label icon="book-open" label="Leggi scheda Wiki" />
                            <span class="sr-only sm:not-sr-only" x-text="selectedPin.wiki_page_title || 'Visualizza'"></span>
                        </a>
                    </div>
                </template>

                <!-- If NOT linked to Wiki page yet: CREATE WIKI PAGE OPTION -->
                <template x-if="!selectedPin.wiki_page_url">
                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs">
                            <div class="text-amber-300 font-semibold mb-1 flex items-center gap-1.5">
                                <x-app-icon name="info" class="w-4 h-4 text-amber-400" />
                                <span>Nessuna scheda wiki collegata</span>
                            </div>
                            <p class="text-slate-400 text-[11px] leading-relaxed">Puoi creare una nuova pagina per descrivere la storia, i pericoli e i PNG di questo luogo.</p>
                        </div>

                        <!-- Direct button to create wiki page pre-filled from this pin -->
                        <a :href="'/campaigns/{{ $campaign->id }}/wiki/create?pin_id=' + selectedPin.id + '&title=' + encodeURIComponent(selectedPin.title) + '&category=' + encodeURIComponent('Luoghi & Città')"
                           class="btn-primary w-full text-xs min-h-11 justify-center shadow-lg">
                            <x-icon-label icon="plus" label="Crea Pagina Wiki" showOnMobile="true"/>
                        </a>

                        <!-- Or link to an existing wiki page -->
                        <div class="pt-2 border-t border-white/[0.06]">
                            <x-parent-page-select :parent-pages="$campaign->wikiPages" name="pin_link_wiki_page_id"
                                label="Collega a una scheda esistente" modelable="linkWikiId" />
                        </div>
                    </div>
                </template>

                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">Colore</span>
                    <div class="flex items-center gap-3 flex-wrap">
                        <template x-for="option in pinColors" :key="option.value">
                            <button type="button" @click="changePinColor(option.value)" :disabled="savingPinColor"
                                    :title="option.label" :aria-label="option.label"
                                    class="h-6 w-6 rounded-full border-2 transition disabled:opacity-50 hover:scale-110"
                                    :class="selectedPin.color === option.value ? 'border-white scale-110' : 'border-transparent hover:border-white/50'"
                                    :style="'background-color: ' + option.value"></button>
                        </template>
                    </div>
                </div>

                <p x-show="mapError" x-text="mapError" role="alert" class="text-xs text-rose-300"></p>
                <p class="text-[11px] text-slate-500 pt-1">Trascina il pin sulla mappa per riposizionarlo</p>
                <div class="flex items-center justify-between gap-2 text-xs">
                    <button type="button" @click="togglePinLock(selectedPin)" :disabled="savingPinLock"
                            class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-amber-300 hover:text-amber-200 disabled:opacity-50">
                        <x-app-icon name="lock-keyhole" class="h-4 w-4" />
                        <span class="icon-label-mobile" :data-tooltip="selectedPin.locked ? 'Sblocca segnaposto' : 'Blocca segnaposto'" x-text="selectedPin.locked ? 'Sblocca segnaposto' : 'Blocca segnaposto'"></span>
                    </button>
                    <button type="button" @click="deletePin(selectedPin)" class="shrink-0 whitespace-nowrap text-rose-400 hover:text-rose-300 text-xs">
                        <x-icon-label icon="trash-2" label="Elimina segnaposto" />
                    </button>
                </div>
            </div>
                </div>
            </template>
            </div>
            <template x-if="modalMode === 'create' || modalMode === 'edit'">
                <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-white/10 px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" @click="modalMode === 'create' ? modalOpen = false : modalMode = 'view'" class="btn-secondary min-h-11 w-full px-3 text-xs sm:w-auto"><x-icon-label icon="x" label="Annulla" :show-on-mobile="true" /></button>
                    <button type="submit" form="pin-dialog-form" :disabled="savingPinDetails || !pinForm.title.trim()" class="btn-primary min-h-11 w-full px-4 text-xs disabled:opacity-50 sm:w-auto"><x-icon-label icon="check" label="Salva segnaposto" :show-on-mobile="true" /></button>
                </div>
            </template>
        </div>
    </dialog>
    </template>

    <!-- Upload Custom Map Modal -->
    <div x-show="uploadModalOpen" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-cloak>
        <div @click.away="uploadModalOpen = false" class="glass-panel p-6 sm:p-8 rounded-2xl max-w-lg w-full relative">
            <button @click="uploadModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1" aria-label="Chiudi caricamento mappa">
                <x-app-icon name="x" class="w-5 h-5" />
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mx-auto mb-3">
                    <x-app-icon name="image" class="w-6 h-6" />
                </div>
                <h3 class="font-['Cinzel'] text-xl font-bold text-white mb-1">Carica Mappa di Gioco</h3>
                <p class="text-xs text-slate-400">Carica una mappa geografica, mappa regionale o piantina di un dungeon (PNG, JPG, WebP fino a 50MB).</p>
            </div>

            <form @submit.prevent="uploadMap($event.target)" class="space-y-4">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Titolo della Mappa *</label>
                    <input type="text" name="title" required class="input-premium text-sm font-semibold" placeholder="es. Il Continente d'Ambra, I Sotterranei di Kar-Drak" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">File Immagine *</label>
                    <input type="file" name="image" required accept="image/*" class="input-premium text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30" />
                </div>

                <div x-show="uploadingMap" class="h-1.5 w-full overflow-hidden rounded-full bg-white/10" x-cloak>
                    <div class="h-full bg-amber-400 transition-all" :style="'width: ' + uploadProgress + '%'"></div>
                </div>

                <p x-show="uploadError" x-text="uploadError" role="alert" class="text-xs text-rose-300" x-cloak></p>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="uploadModalOpen = false" :disabled="uploadingMap" class="btn-secondary text-xs py-2 px-3 disabled:opacity-50"><x-icon-label icon="x" label="Annulla" /></button>
                    <button type="submit" :disabled="uploadingMap" class="btn-primary text-xs py-2 px-4 disabled:opacity-50">
                        <x-app-icon name="image" />
                        <span class="icon-label-mobile" data-tooltip="Carica mappa" x-text="uploadingMap ? 'Caricamento… ' + uploadProgress + '%' : 'Carica Mappa'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function customImageMap(config) {
    let scrollLock = null;

    return {
        campaignId: config.campaignId,
        activeMapId: config.activeMapId,
        maps: config.maps,
        pins: config.initialPins,
        showLabels: true,
        zoomLevel: 100,
        mapFitWidth: 0,
        mapFitHeight: 0,
        isPanning: false,
        lastPanAt: 0,
        mapTouchMode: null,
        mapTouchStart: null,
        pinchStartDistance: 0,
        pinchStartZoom: 100,
        isPinchZooming: false,
        mapError: '',
        savingPinLock: false,
        modalOpen: false,
        modalMode: 'view',
        pinForm: { title: '', description: '', color: '#e11d48', wiki_page_id: '' },
        linkWikiId: '',
        uploadModalOpen: false,
        uploadingMap: false,
        uploadProgress: 0,
        uploadError: '',
        selectedPin: {},
        newPinCoords: { x: 50, y: 50 },
        draggingPin: null,
        isDragging: false,
        dragOrigin: null,
        pendingPinChanges: [],
        pendingMapChanges: [],
        savingPinIds: new Set(),

        init() {
            this.$watch('modalOpen', (isOpen) => {
                if (isOpen) {
                    const body = document.body;
                    scrollLock = {
                        scrollY: window.scrollY,
                        position: body.style.position,
                        top: body.style.top,
                        left: body.style.left,
                        right: body.style.right,
                        width: body.style.width,
                    };
                    body.style.position = 'fixed';
                    body.style.top = `-${scrollLock.scrollY}px`;
                    body.style.left = '0';
                    body.style.right = '0';
                    body.style.width = '100%';
                    this.$nextTick(() => {
                        if (this.modalOpen && !this.$refs.pinDialog.open) {
                            this.$refs.pinDialog.showModal();
                        }
                    });
                } else {
                    if (this.$refs.pinDialog.open) {
                        this.$refs.pinDialog.close();
                    }
                    if (scrollLock) {
                        const body = document.body;
                        body.style.position = scrollLock.position;
                        body.style.top = scrollLock.top;
                        body.style.left = scrollLock.left;
                        body.style.right = scrollLock.right;
                        body.style.width = scrollLock.width;
                        window.scrollTo(0, scrollLock.scrollY);
                        scrollLock = null;
                    }
                }
            });
            this.$watch('linkWikiId', (wikiId) => {
                if (wikiId && this.modalOpen && this.modalMode === 'view' && this.selectedPin.id) {
                    this.linkPinToWiki(this.selectedPin, wikiId);
                }
            });
            this.$nextTick(() => {
                this.mapResizeObserver = new ResizeObserver(() => this.fitMap());
                this.mapResizeObserver.observe(this.$refs.mapViewport);
                this.fitMap();
            });
            window.campaignSyncManager?.registerEntity('pin', {
                applyChange: (change) => this.applyPinChange(change),
                onStatsUpdate: () => {},
                onReset: (items) => {
                    this.pins = items;
                    this.pendingPinChanges = [];
                    const selectedPin = this.pins.find(pin => pin.id === this.selectedPin?.id);
                    if (selectedPin) this.selectedPin = { ...selectedPin };
                    else if (this.modalOpen && this.modalMode !== 'create') this.modalOpen = false;
                },
            });
            window.campaignSyncManager?.registerEntity('map', {
                applyChange: (change) => this.applyMapChange(change),
                onStatsUpdate: () => {},
                onReset: (items) => {
                    if (Array.isArray(items)) {
                        this.maps = items.map(map => ({
                            id: map.id,
                            title: map.title,
                            image_url: map.image_url,
                            is_default: !!map.is_default,
                            pins_count: map.pins_count ?? 0,
                        }));
                    }
                    this.pendingMapChanges = [];
                    if (!this.maps.find(m => m.id === this.activeMapId)) {
                        const def = this.maps.find(m => m.is_default) || this.maps[0];
                        if (def) {
                            this.activeMapId = def.id;
                        }
                    }
                },
            });
        },

        destroy() {
            this.mapResizeObserver?.disconnect();
        },

        applyPinChange(change) {
            if (this.draggingPin?.id === change.id || this.savingPinIds.has(change.id)) {
                this.pendingPinChanges.push(change);
                return;
            }
            const index = this.pins.findIndex(pin => pin.id === change.id);
            if (index !== -1 && Number(this.pins[index].sync_version || 0) >= Number(change.sync_version || 0)) {
                return;
            }
            if (change.deleted) {
                if (index !== -1) this.pins.splice(index, 1);
                if (this.selectedPin.id === change.id && this.modalMode !== 'create') this.modalOpen = false;
                return;
            }
            if (index === -1) this.pins.push(change);
            else Object.assign(this.pins[index], change);
            if (this.selectedPin.id === change.id && this.modalMode !== 'edit') {
                this.selectedPin = { ...change };
            }
        },

        applyPendingPinChanges(pinId) {
            const pending = this.pendingPinChanges.filter(change => change.id === pinId);
            this.pendingPinChanges = this.pendingPinChanges.filter(change => change.id !== pinId);
            pending.forEach(change => this.applyPinChange(change));
        },

        applyMapChange(change) {
            const index = this.maps.findIndex(map => map.id === change.id);
            if (index !== -1 && Number(this.maps[index].sync_version || 0) >= Number(change.sync_version || 0)) {
                return;
            }
            if (change.deleted) {
                if (index !== -1) this.maps.splice(index, 1);
                if (this.activeMapId === change.id) {
                    const def = this.maps.find(m => m.is_default) || this.maps[0];
                    this.activeMapId = def ? def.id : null;
                }
                return;
            }
            if (index === -1) {
                this.maps.push({
                    id: change.id,
                    title: change.title,
                    image_url: change.image_url,
                    is_default: !!change.is_default,
                    pins_count: change.pins_count ?? 0,
                });
            } else {
                Object.assign(this.maps[index], {
                    id: change.id,
                    title: change.title,
                    image_url: change.image_url,
                    is_default: !!change.is_default,
                    pins_count: change.pins_count ?? this.maps[index].pins_count ?? 0,
                    sync_version: change.sync_version,
                });
            }
            if (change.is_default) {
                this.maps.forEach((m, i) => {
                    if (i !== index && this.maps[i].id !== change.id) {
                        this.maps[i].is_default = false;
                    }
                });
                if (index === -1) {
                    const added = this.maps.find(m => m.id === change.id);
                    if (added) added.is_default = true;
                } else {
                    this.maps[index].is_default = true;
                }
            }
        },

        get mapCanvasStyle() {
            const zoom = this.zoomLevel / 100;
            return `width: ${this.mapFitWidth * zoom}px; height: ${this.mapFitHeight * zoom}px;`;
        },

        get activeMap() {
            if (!this.activeMapId && this.maps.length > 0) {
                return this.maps[0];
            }
            return this.maps.find(m => m.id === this.activeMapId) || null;
        },

        get currentMapUrl() {
            if (this.activeMap) {
                return this.activeMap.image_url;
            }
            return config.fallbackMapUrl;
        },

        get currentPins() {
            if (!this.activeMapId) {
                return this.pins;
            }
            return this.pins.filter(p => !p.campaign_map_id || p.campaign_map_id === this.activeMapId);
        },

        getPinsForMap(mapId) {
            return this.pins.filter(p => p.campaign_map_id === mapId);
        },

        selectMap(mapId) {
            this.activeMapId = mapId;
            this.resetZoom();
            this.$nextTick(() => this.fitMap());
        },

        fitMap() {
            const image = this.$refs.mapImage;
            const viewport = this.$refs.mapViewport;
            if (!image?.naturalWidth || !image?.naturalHeight || !viewport.clientWidth || !viewport.clientHeight) {
                return;
            }

            const fitScale = Math.min(
                viewport.clientWidth / image.naturalWidth,
                viewport.clientHeight / image.naturalHeight
            );
            this.mapFitWidth = image.naturalWidth * fitScale;
            this.mapFitHeight = image.naturalHeight * fitScale;
        },

        zoomIn() {
            this.zoomLevel = Math.min(300, this.zoomLevel + 25);
        },

        setZoomAt(zoomLevel, clientX, clientY) {
            const viewport = this.$refs.mapViewport;
            const image = this.$refs.mapImage;
            if (!image?.naturalWidth || !image?.naturalHeight) {
                return;
            }
            const viewportRect = viewport.getBoundingClientRect();
            const imageRect = image.getBoundingClientRect();
            if (!imageRect.width || !imageRect.height) {
                return;
            }
            const nextZoom = Math.round(Math.max(50, Math.min(300, zoomLevel)) * 10) / 10;
            const imagePointX = Math.max(0, Math.min(1, (clientX - imageRect.left) / imageRect.width));
            const imagePointY = Math.max(0, Math.min(1, (clientY - imageRect.top) / imageRect.height));

            this.zoomLevel = nextZoom;
            this.$nextTick(() => {
                const zoom = nextZoom / 100;
                const imageWidth = this.mapFitWidth * zoom;
                const imageHeight = this.mapFitHeight * zoom;
                const imageLeft = Math.max(0, (viewport.clientWidth - imageWidth) / 2);
                const imageTop = Math.max(0, (viewport.clientHeight - imageHeight) / 2);
                const viewportX = clientX - viewportRect.left;
                const viewportY = clientY - viewportRect.top;

                viewport.scrollLeft = Math.max(0, imageLeft + imagePointX * imageWidth - viewportX);
                viewport.scrollTop = Math.max(0, imageTop + imagePointY * imageHeight - viewportY);
            });
        },

        handleMapWheel(event) {
            if (!event.ctrlKey) {
                return;
            }

            event.preventDefault();
            this.setZoomAt(this.zoomLevel * Math.exp(-event.deltaY * 0.002), event.clientX, event.clientY);
        },

        startMapTouch(event) {
            if (event.touches.length >= 2) {
                const [firstTouch, secondTouch] = event.touches;
                this.mapTouchMode = 'pinch';
                this.isPinchZooming = true;
                this.pinchStartDistance = Math.hypot(
                    secondTouch.clientX - firstTouch.clientX,
                    secondTouch.clientY - firstTouch.clientY
                );
                this.pinchStartZoom = this.zoomLevel;
                this.isPanning = false;
                return;
            }

            if (event.target.closest('[data-map-pin]')) {
                return;
            }

            const touch = event.touches[0];
            const viewport = this.$refs.mapViewport;
            this.mapTouchMode = 'pan';
            this.mapTouchStart = {
                clientX: touch.clientX,
                clientY: touch.clientY,
                scrollLeft: viewport.scrollLeft,
                scrollTop: viewport.scrollTop,
            };
            this.isPanning = true;
        },

        moveMapTouch(event) {
            if (event.touches.length >= 2) {
                const [firstTouch, secondTouch] = event.touches;
                const distance = Math.hypot(
                    secondTouch.clientX - firstTouch.clientX,
                    secondTouch.clientY - firstTouch.clientY
                );

                if (this.mapTouchMode !== 'pinch') {
                    this.mapTouchMode = 'pinch';
                    this.isPinchZooming = true;
                    this.pinchStartDistance = distance;
                    this.pinchStartZoom = this.zoomLevel;
                }

                if (this.pinchStartDistance > 0) {
                    this.setZoomAt(
                        this.pinchStartZoom * distance / this.pinchStartDistance,
                        (firstTouch.clientX + secondTouch.clientX) / 2,
                        (firstTouch.clientY + secondTouch.clientY) / 2
                    );
                }

                return;
            }

            if (this.mapTouchMode !== 'pan' || !this.mapTouchStart || event.touches.length !== 1) {
                return;
            }

            const touch = event.touches[0];
            const viewport = this.$refs.mapViewport;
            viewport.scrollLeft = this.mapTouchStart.scrollLeft - (touch.clientX - this.mapTouchStart.clientX);
            viewport.scrollTop = this.mapTouchStart.scrollTop - (touch.clientY - this.mapTouchStart.clientY);
        },

        endMapTouch(event) {
            if (event.touches.length < 2) {
                this.isPinchZooming = false;
                this.pinchStartDistance = 0;
            }

            if (event.touches.length === 0 || this.mapTouchMode === 'pinch') {
                this.mapTouchMode = null;
                this.mapTouchStart = null;
                this.isPanning = false;
            }
        },

        zoomOut() {
            this.zoomLevel = Math.max(50, this.zoomLevel - 25);
        },

        resetZoom() {
            this.zoomLevel = 100;
        },

        handleMapInsert(event) {
            if (this.isDragging || Date.now() - this.lastPanAt < 350 || event.target !== this.$refs.mapImage) {
                return;
            }

            const rect = this.$refs.mapImage.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const clickY = event.clientY - rect.top;

            // Compute percentage coordinates (0.0 to 100.0)
            const xPercent = Math.max(0, Math.min(100, (clickX / rect.width) * 100));
            const yPercent = Math.max(0, Math.min(100, (clickY / rect.height) * 100));

            this.newPinCoords = { x: xPercent, y: yPercent };
            this.pinForm = {
                title: '',
                description: '',
                color: '#e11d48',
                wiki_page_id: ''
            };
            this.openCreateModal();
        },

        startPan(event) {
            if (event.button !== 0 || event.target.closest('[data-map-pin]')) {
                return;
            }

            const viewport = this.$refs.mapViewport;
            const startX = event.clientX;
            const startY = event.clientY;
            const startScrollLeft = viewport.scrollLeft;
            const startScrollTop = viewport.scrollTop;
            let moved = false;
            this.isPanning = true;
            event.preventDefault();

            const onMouseMove = (moveEvent) => {
                const deltaX = moveEvent.clientX - startX;
                const deltaY = moveEvent.clientY - startY;
                moved ||= Math.abs(deltaX) > 2 || Math.abs(deltaY) > 2;
                viewport.scrollLeft = startScrollLeft - deltaX;
                viewport.scrollTop = startScrollTop - deltaY;
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                this.isPanning = false;
                if (moved) {
                    this.lastPanAt = Date.now();
                }
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        openPinPopover(pin, event) {
            if (this.isDragging) return;
            this.openPinModal(pin);
        },

        openCreateModal() {
            this.mapError = '';
            this.modalOpen = true;
            this.modalMode = 'create';
        },

        openPinModal(pin) {
            this.selectedPin = pin;
            this.mapError = '';
            this.linkWikiId = '';
            this.modalOpen = true;
            this.modalMode = 'view';
        },

        savingPinDetails: false,

        startPinEdit() {
            this.pinForm = {
                title: this.selectedPin.title,
                description: this.selectedPin.description ?? '',
                color: this.selectedPin.color,
                wiki_page_id: this.selectedPin.wiki_page_id ? String(this.selectedPin.wiki_page_id) : '',
            };
            this.mapError = '';
            this.modalMode = 'edit';
        },

        async savePinDetails() {
            const pin = this.pins.find((item) => item.id === this.selectedPin.id);
            if (!pin) {
                this.mapError = 'Il segnaposto non è più disponibile.';
                return;
            }

            this.savingPinDetails = true;
            this.mapError = '';

            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.pinForm.title.trim(),
                        description: this.pinForm.description.trim() || null,
                        color: this.pinForm.color,
                        wiki_page_id: this.pinForm.wiki_page_id || null,
                    })
                });
                if (!response.ok) {
                    throw new Error(`Impossibile aggiornare il segnaposto (${response.status}).`);
                }

                const data = await response.json();
                pin.title = data.pin.title;
                pin.description = data.pin.description;
                pin.color = data.pin.color;
                pin.wiki_page_id = data.pin.wiki_page_id;
                pin.wiki_page_title = data.pin.wiki_page ? data.pin.wiki_page.title : null;
                pin.wiki_page_url = data.pin.wiki_page ? `/campaigns/${config.campaignId}/wiki/${data.pin.wiki_page_id}` : null;
                pin.sync_version = data.pin.sync_version;
                this.selectedPin = { ...pin };
                this.modalMode = 'view';
            } catch (error) {
                this.mapError = error.message;
                console.error(error);
            } finally {
                this.savingPinDetails = false;
            }
        },

        pinColors: [
            { value: '#e11d48', label: 'Rosso Cremisi' },
            { value: '#f59e0b', label: 'Oro / Ambra' },
            { value: '#10b981', label: 'Smeraldo' },
            { value: '#06b6d4', label: 'Azzurro Arcano' },
            { value: '#8b5cf6', label: 'Viola Mistico' },
        ],
        savingPinColor: false,

        async uploadMap(form) {
            const file = form.elements.image.files[0];
            if (!file) {
                return;
            }
            if (file.size > config.mapMaxBytes) {
                this.uploadError = `Il file supera il limite di ${Math.round(config.mapMaxBytes / 1048576)}MB.`;
                return;
            }

            this.uploadingMap = true;
            this.uploadProgress = 0;
            this.uploadError = '';

            const headers = { 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' };
            const uploadId = crypto.randomUUID();
            const totalChunks = Math.max(1, Math.ceil(file.size / config.mapChunkSize));

            try {
                for (let index = 0; index < totalChunks; index++) {
                    const body = new FormData();
                    body.append('upload_id', uploadId);
                    body.append('index', index);
                    body.append('chunk', file.slice(index * config.mapChunkSize, (index + 1) * config.mapChunkSize), 'chunk');

                    await this.sendUploadRequest(config.storeMapChunkUrl, { method: 'POST', headers, body });
                    this.uploadProgress = Math.round(((index + 1) / totalChunks) * 100);
                }

                await this.sendUploadRequest(config.storeMapUrl, {
                    method: 'POST',
                    headers: { ...headers, 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: form.elements.title.value,
                        upload_id: uploadId,
                        total_chunks: totalChunks,
                    })
                });

                window.location.reload();
            } catch (error) {
                this.uploadError = error.message;
                this.uploadingMap = false;
            }
        },

        async sendUploadRequest(url, options) {
            const response = await fetch(url, options);
            if (response.ok) {
                return response.json();
            }

            let message = `Caricamento non riuscito (${response.status}).`;
            try {
                const data = await response.json();
                message = Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? message;
            } catch (e) {
                // La risposta non è JSON: si mantiene il messaggio generico.
            }
            throw new Error(message);
        },

        async changePinColor(color) {
            const pin = this.pins.find((item) => item.id === this.selectedPin.id);
            if (!pin || pin.color === color) {
                return;
            }

            this.savingPinColor = true;
            this.mapError = '';

            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ color })
                });
                if (!response.ok) {
                    throw new Error(`Impossibile aggiornare il colore del segnaposto (${response.status}).`);
                }

                const data = await response.json();
                pin.color = data.pin.color;
                pin.sync_version = data.pin.sync_version;
                this.selectedPin = { ...this.selectedPin, color: pin.color };
            } catch (error) {
                this.mapError = error.message;
                console.error(error);
            } finally {
                this.savingPinColor = false;
            }
        },

        async togglePinLock(pin) {
            this.savingPinLock = true;
            this.mapError = '';

            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ locked: !pin.locked })
                });
                if (!response.ok) {
                    throw new Error(`Impossibile aggiornare il blocco del segnaposto (${response.status}).`);
                }

                const data = await response.json();
                pin.locked = data.pin.locked;
                pin.sync_version = data.pin.sync_version;
                this.selectedPin = { ...pin };
            } catch (error) {
                this.mapError = error.message;
                console.error(error);
            } finally {
                this.savingPinLock = false;
            }
        },

        async saveNewPin() {
            this.savingPinDetails = true;
            this.mapError = '';
            try {
                const payload = {
                    title: this.pinForm.title,
                    description: this.pinForm.description,
                    x: this.newPinCoords.x,
                    y: this.newPinCoords.y,
                    color: this.pinForm.color,
                    campaign_map_id: this.activeMapId,
                    wiki_page_id: this.pinForm.wiki_page_id || null
                };

                const response = await fetch(config.storePinUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    throw new Error(`Impossibile creare il segnaposto (${response.status}).`);
                }
                const data = await response.json();
                const newPin = {
                    id: data.pin.id,
                    campaign_map_id: data.pin.campaign_map_id,
                    title: data.pin.title,
                    description: data.pin.description,
                    x: parseFloat(data.pin.x),
                    y: parseFloat(data.pin.y),
                    color: data.pin.color,
                    locked: data.pin.locked,
                    wiki_page_id: data.pin.wiki_page_id,
                    wiki_page_title: data.pin.wiki_page ? data.pin.wiki_page.title : null,
                    wiki_page_url: data.pin.wiki_page ? `/campaigns/${config.campaignId}/wiki/${data.pin.wiki_page.id}` : null,
                    sync_version: data.pin.sync_version,
                };
                const existing = this.pins.find(pin => pin.id === newPin.id);
                if (existing) {
                    Object.assign(existing, newPin);
                } else {
                    this.pins.push(newPin);
                }
                this.modalOpen = false;
            } catch (err) {
                this.mapError = err.message;
                console.error(err);
            } finally {
                this.savingPinDetails = false;
            }
        },

        async linkPinToWiki(pin, wikiId) {
            if (!wikiId) return;

            this.mapError = '';
            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        wiki_page_id: wikiId
                    })
                });

                if (!response.ok) {
                    throw new Error(`Impossibile collegare la scheda wiki (${response.status}).`);
                }
                const data = await response.json();
                pin.wiki_page_id = data.pin.wiki_page_id;
                pin.wiki_page_title = data.pin.wiki_page ? data.pin.wiki_page.title : null;
                pin.wiki_page_url = `/campaigns/${config.campaignId}/wiki/${wikiId}`;
                pin.sync_version = data.pin.sync_version;
                this.selectedPin = { ...pin };
            } catch (err) {
                this.mapError = err.message;
                this.linkWikiId = '';
                console.error(err);
            }
        },

        async deletePin(pin) {
            if (!confirm(`Vuoi rimuovere il segnaposto "${pin.title}"?`)) return;

            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.pins = this.pins.filter(p => p.id !== pin.id);
                    this.modalOpen = false;
                }
            } catch (err) {
                console.error(err);
            }
        },

        // Drag and Drop (Mouse)
        startDrag(event, pin) {
            if (event.button !== 0 || pin.locked) return;
            event.preventDefault();
            this.draggingPin = pin;
            this.isDragging = false;
            this.dragOrigin = { x: pin.x, y: pin.y };

            const mapImg = this.$refs.mapImage;
            if (!mapImg) return;

            const onMouseMove = (moveEvent) => {
                this.isDragging = true;
                const rect = mapImg.getBoundingClientRect();
                const xPercent = Math.max(0, Math.min(100, ((moveEvent.clientX - rect.left) / rect.width) * 100));
                const yPercent = Math.max(0, Math.min(100, ((moveEvent.clientY - rect.top) / rect.height) * 100));
                pin.x = xPercent;
                pin.y = yPercent;
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                if (this.isDragging && this.draggingPin) {
                    this.savePinCoordinates(this.draggingPin, this.dragOrigin);
                    setTimeout(() => { this.isDragging = false; }, 50);
                }
                this.draggingPin = null;
                this.dragOrigin = null;
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        // Drag and Drop (Touch for mobile)
        startTouchDrag(event, pin) {
            if (pin.locked || event.touches.length > 1) return;
            this.draggingPin = pin;
            this.isDragging = false;
            this.dragOrigin = { x: pin.x, y: pin.y };

            const mapImg = this.$refs.mapImage;
            if (!mapImg) return;

            const onTouchMove = (moveEvent) => {
                moveEvent.preventDefault();
                if (this.isPinchZooming) return;
                this.isDragging = true;
                const touch = moveEvent.touches[0];
                const rect = mapImg.getBoundingClientRect();
                const xPercent = Math.max(0, Math.min(100, ((touch.clientX - rect.left) / rect.width) * 100));
                const yPercent = Math.max(0, Math.min(100, ((touch.clientY - rect.top) / rect.height) * 100));
                pin.x = xPercent;
                pin.y = yPercent;
            };

            const onTouchEnd = () => {
                document.removeEventListener('touchmove', onTouchMove);
                document.removeEventListener('touchend', onTouchEnd);

                if (this.isDragging && this.draggingPin) {
                    this.savePinCoordinates(this.draggingPin, this.dragOrigin);
                    setTimeout(() => { this.isDragging = false; }, 100);
                }
                this.draggingPin = null;
                this.dragOrigin = null;
            };

            document.addEventListener('touchmove', onTouchMove, { passive: false });
            document.addEventListener('touchend', onTouchEnd);
        },

        async savePinCoordinates(pin, originalPosition) {
            this.savingPinIds.add(pin.id);
            try {
                const response = await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        x: pin.x,
                        y: pin.y
                    })
                });
                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || `Impossibile salvare la posizione del segnaposto (${response.status}).`);
                }
                const data = await response.json();
                Object.assign(pin, data.pin);
            } catch (error) {
                pin.x = originalPosition.x;
                pin.y = originalPosition.y;
                this.mapError = error.message;
                this.selectedPin = pin;
                this.modalOpen = true;
                this.modalMode = 'view';
                console.error(error);
            } finally {
                this.savingPinIds.delete(pin.id);
                this.applyPendingPinChanges(pin.id);
            }
        }
    }
}
</script>
