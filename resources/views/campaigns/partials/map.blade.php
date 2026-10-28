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
    ])) }},
    storePinUrl: '{{ route('pins.store', $campaign) }}',
    updatePinBaseUrl: '{{ url("/campaigns/{$campaign->id}/pins") }}',
    storeMapUrl: '{{ route('maps.store', $campaign) }}',
    storeMapChunkUrl: '{{ route('maps.storeChunk', $campaign) }}',
    mapChunkSize: {{ \App\Http\Controllers\CampaignMapController::MAX_CHUNK_BYTES }},
    mapMaxBytes: {{ \App\Http\Controllers\CampaignMapController::MAX_IMAGE_BYTES }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-4">

    <!-- Top Map Selector & Upload Actions -->
    <div class="glass-panel p-3.5 sm:p-4 rounded-xl flex flex-wrap items-center justify-between gap-3">
        <!-- Map Tabs or Selector -->
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none max-w-full">
            <template x-if="maps.length === 0">
                <span class="text-xs text-amber-300 font-semibold px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Mappa Predefinita (Esempio)
                </span>
            </template>

            <template x-for="map in maps" :key="map.id">
                <button @click="selectMap(map.id)" 
                        :class="activeMapId === map.id ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'bg-white/5 text-slate-400 hover:text-white border-white/5'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all flex items-center gap-1.5 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full" :class="map.is_default ? 'bg-amber-400' : 'bg-slate-400'"></span>
                    <span x-text="map.title"></span>
                    <span class="text-[10px] opacity-70" x-text="'(' + (getPinsForMap(map.id).length) + ')'"></span>
                </button>
            </template>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Labels Toggle -->
            {{-- <button @click="showLabels = !showLabels"
                    :class="showLabels ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 border-white/5'"
                    class="hidden">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span class="hidden sm:inline">Etichette</span>
            </button> --}}

            <!-- Upload New Map Button -->
            <button @click="uploadModalOpen = true" class="btn-primary text-xs py-1.5 px-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Carica Mappa</span>
            </button>
        </div>
    </div>

    <div class="glass-panel p-2 rounded-xl flex flex-wrap items-center justify-between gap-2">
        <button @click="showLabels = !showLabels"
                :class="showLabels ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 border-white/5'"
                class="btn-secondary !py-1.5 !px-3 text-xs border"
                aria-label="Mostra o nascondi le etichette con nome">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <span>Etichette</span>
        </button>

        <div class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-black/40 p-0.5"
             role="group"
             aria-label="Controlli zoom mappa">
            <button type="button" @click="zoomOut" :disabled="zoomLevel <= 50"
                    class="h-8 w-8 rounded text-lg leading-none text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Riduci zoom" title="Riduci zoom">−</button>
            <span class="min-w-12 px-1 text-center text-xs font-mono text-slate-200" x-text="(Math.round(zoomLevel * 10) / 10) + '%'"></span>
            <button type="button" @click="zoomIn" :disabled="zoomLevel >= 300"
                    class="h-8 w-8 rounded text-lg leading-none text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Aumenta zoom" title="Aumenta zoom">+</button>
            <button type="button" @click="resetZoom" :disabled="zoomLevel === 100"
                    class="rounded px-2 py-1.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Reimposta zoom al 100%" title="Reimposta zoom">Adatta</button>
        </div>
    </div>

    <!-- Active Map Management (GM) -->
    <template x-if="activeMap">
        <div class="flex items-center justify-between text-xs px-2 text-slate-400">
            <div>
                Mappa visualizzata: <strong class="text-white" x-text="activeMap.title"></strong>
                <span x-show="activeMap.is_default" class="ml-1 text-[10px] text-amber-400 font-bold uppercase">(Predefinita)</span>
            </div>
            <div class="flex items-center gap-2">
                @if($isGm)
                    <template x-if="!activeMap.is_default">
                        <form :action="'/campaigns/{{ $campaign->id }}/maps/' + activeMap.id + '/default'" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-amber-400 hover:underline text-[11px]">Imposta come predefinita</button>
                        </form>
                    </template>
                    <form :action="'/campaigns/{{ $campaign->id }}/maps/' + activeMap.id" method="POST" onsubmit="return confirm('Eliminare questa mappa e i suoi segnaposto?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-400 hover:text-rose-300 text-[11px] ml-2">Elimina Mappa</button>
                    </form>
                @endif
            </div>
        </div>
    </template>

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
                <template x-for="pin in currentPins" :key="pin.id">
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
                            <div class="h-5 w-5 rounded-full flex items-center justify-center text-white border-2 border-white shadow-xl transition-transform group-hover:scale-125"
                                 :style="'background-color: ' + pin.color + '; box-shadow: 0 0 12px ' + pin.color + ', 0 4px 8px rgba(0,0,0,0.6);'">
                                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
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
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Luoghi Segnati (<span x-text="currentPins.length"></span>)</span>
            </h3>
        </div>

        <template x-if="currentPins.length === 0">
            <p class="text-xs text-slate-400 text-center py-4">Nessun punto segnato su questa mappa. Clicca sull'immagine per posizionare il primo pin!</p>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
            <template x-for="pin in currentPins" :key="pin.id">
                <div class="bg-white/[0.03] hover:bg-white/[0.07] border border-white/[0.07] p-2.5 rounded-lg flex items-center justify-between gap-2 transition-colors">
                    <button @click="openPinPopover(pin)" class="flex items-center gap-2.5 text-left flex-1 min-w-0">
                        <span class="w-3 h-3 rounded-full shrink-0 shadow-sm" :style="'background-color: ' + pin.color"></span>
                        <div class="truncate">
                            <div class="text-xs font-semibold text-slate-200 truncate" x-text="pin.title"></div>
                            <div class="text-[10px] text-slate-400 truncate" x-text="pin.description || 'Coordinate: ' + pin.x.toFixed(1) + '%, ' + pin.y.toFixed(1) + '%'"></div>
                        </div>
                    </button>

                    <div class="flex items-center gap-1 shrink-0">
                        <template x-if="pin.wiki_page_url">
                            <a :href="pin.wiki_page_url" class="p-1 text-amber-400 hover:text-amber-300" title="Apri Scheda Wiki">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </a>
                        </template>

                        <template x-if="!pin.wiki_page_url">
                            <a :href="'/campaigns/{{ $campaign->id }}/wiki/create?pin_id=' + pin.id + '&title=' + encodeURIComponent(pin.title) + '&category=' + encodeURIComponent('Luoghi & Città')"
                               class="p-1 text-emerald-400 hover:text-emerald-300" title="Crea Scheda Wiki per questo Luogo">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </a>
                        </template>

                        <button @click="deletePin(pin)" class="p-1 text-slate-500 hover:text-rose-400" title="Elimina Segnaposto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Create Pin Modal -->
    <div x-show="createModalOpen" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-cloak>
        <div @click.away="createModalOpen = false" class="glass-panel p-6 rounded-2xl max-w-md w-full relative">
            <button @click="createModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="font-['Cinzel'] text-lg font-bold text-white mb-1">Nuovo Punto sulla Mappa</h3>
            <p class="text-xs text-slate-400 mb-4" x-text="'Posizione: X ' + newPinCoords.x.toFixed(1) + '% • Y ' + newPinCoords.y.toFixed(1) + '%'"></p>

            <form @submit.prevent="saveNewPin" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Nome del Luogo o Etichetta *</label>
                    <input type="text" x-model="newPinForm.title" required autofocus class="input-premium text-sm font-semibold" placeholder="es. Cittadella delle Ossa, Locanda del Lupo" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Descrizione / Note rapide</label>
                    <textarea x-model="newPinForm.description" rows="2" class="input-premium text-xs" placeholder="Chi vive qui? Quali indizi o pericoli attendono gli avventurieri?"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Colore Segnaposto</label>
                        <select x-model="newPinForm.color" class="input-premium text-xs">
                            <option value="#e11d48">Rosso Cremisi</option>
                            <option value="#f59e0b">Oro / Ambra</option>
                            <option value="#10b981">Smeraldo</option>
                            <option value="#06b6d4">Azzurro Arcano</option>
                            <option value="#8b5cf6">Viola Mistico</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Collega a Scheda Wiki</label>
                        <select x-model="newPinForm.wiki_page_id" class="input-premium text-xs">
                            <option value="">Nessun collegamento</option>
                            @foreach($campaign->wikiPages as $wp)
                                <option value="{{ $wp->id }}">{{ $wp->title }} ({{ $wp->category }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" @click="createModalOpen = false" class="btn-secondary text-xs py-2 px-3">Annulla</button>
                    <button type="submit" class="btn-primary text-xs py-2 px-4">Salva Segnaposto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pin Details Popover / Modal with DIRECT WIKI CREATION -->
    <div x-show="detailsModalOpen" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-cloak>
        <div @click.away="detailsModalOpen = false" class="glass-panel p-6 rounded-2xl max-w-md w-full relative">
            <button @click="detailsModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <template x-if="!editingPin">
                <div>
                    <div class="flex items-center gap-2.5 mb-2 pr-8">
                        <span class="w-4 h-4 rounded-full shrink-0" :style="'background-color: ' + selectedPin.color"></span>
                        <h3 class="font-['Cinzel'] text-xl font-bold text-white min-w-0 break-words" x-text="selectedPin.title"></h3>
                        <button type="button" @click="startPinEdit()" class="shrink-0 p-1 text-slate-400 hover:text-amber-300" title="Modifica nome e descrizione" aria-label="Modifica nome e descrizione">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2.5 2.5 0 113.536 3.536L12.536 16.536 8 18l1-5z"/></svg>
                        </button>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed mb-4 whitespace-pre-wrap" x-text="selectedPin.description || 'Nessuna descrizione specificata.'"></p>
                </div>
            </template>

            <template x-if="editingPin">
                <form @submit.prevent="savePinDetails()" class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Nome *</label>
                        <input type="text" x-model="pinEditForm.title" required maxlength="255" class="input-premium text-sm font-semibold" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Descrizione</label>
                        <textarea x-model="pinEditForm.description" rows="4" class="input-premium text-xs"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Scheda Wiki collegata</label>
                        <select x-model="pinEditForm.wiki_page_id" class="input-premium text-xs">
                            <option value="">Nessun collegamento</option>
                            @foreach($campaign->wikiPages as $wp)
                                <option value="{{ $wp->id }}">{{ $wp->title }} ({{ $wp->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="editingPin = false" :disabled="savingPinDetails" class="btn-secondary text-xs py-1.5 px-3 disabled:opacity-50">Annulla</button>
                        <button type="submit" :disabled="savingPinDetails || !pinEditForm.title.trim()" class="btn-primary text-xs py-1.5 px-3 disabled:opacity-50">Salva</button>
                    </div>
                </form>
            </template>

            <div class="space-y-3 pt-3 border-t border-white/10">
                <!-- If linked to Wiki page -->
                <template x-if="selectedPin.wiki_page_url">
                    <div class="space-y-2">
                        <a :href="selectedPin.wiki_page_url" class="btn-primary w-full text-xs py-2.5 justify-center shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span x-text="'Leggi Scheda Wiki: ' + (selectedPin.wiki_page_title || 'Visualizza')"></span>
                        </a>
                    </div>
                </template>

                <!-- If NOT linked to Wiki page yet: CREATE WIKI PAGE OPTION -->
                <template x-if="!selectedPin.wiki_page_url">
                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs">
                            <div class="text-amber-300 font-semibold mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Nessuna scheda wiki collegata</span>
                            </div>
                            <p class="text-slate-400 text-[11px] leading-relaxed">Puoi creare una nuova pagina per descrivere la storia, i pericoli e i PNG di questo luogo.</p>
                        </div>

                        <!-- Direct button to create wiki page pre-filled from this pin -->
                        <a :href="'/campaigns/{{ $campaign->id }}/wiki/create?pin_id=' + selectedPin.id + '&title=' + encodeURIComponent(selectedPin.title) + '&category=' + encodeURIComponent('Luoghi & Città')"
                           class="btn-primary w-full text-xs py-2.5 justify-center shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Crea Pagina Wiki per questo Luogo</span>
                        </a>

                        <!-- Or link to an existing wiki page -->
                        <div class="pt-2 border-t border-white/[0.06]" x-data="{ linkWikiId: '' }">
                            <label class="block text-[11px] text-slate-400 mb-1">Oppure collega a una scheda esistente:</label>
                            <div class="flex items-center gap-2">
                                <select x-model="linkWikiId" class="input-premium text-xs py-1.5">
                                    <option value="">Seleziona pagina wiki...</option>
                                    @foreach($campaign->wikiPages as $wp)
                                        <option value="{{ $wp->id }}">{{ $wp->title }} ({{ $wp->category }})</option>
                                    @endforeach
                                </select>
                                <button type="button" @click="linkPinToWiki(selectedPin, linkWikiId)" :disabled="!linkWikiId" class="btn-secondary text-xs py-1.5 px-3 shrink-0 disabled:opacity-50">
                                    Collega
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">Colore</span>
                    <div class="flex items-center gap-2">
                        <template x-for="option in pinColors" :key="option.value">
                            <button type="button" @click="changePinColor(option.value)" :disabled="savingPinColor"
                                    :title="option.label" :aria-label="option.label"
                                    class="h-5 w-5 rounded-full border-2 transition disabled:opacity-50"
                                    :class="selectedPin.color === option.value ? 'border-white scale-110' : 'border-transparent hover:border-white/50'"
                                    :style="'background-color: ' + option.value"></button>
                        </template>
                    </div>
                </div>

                <p x-show="mapError" x-text="mapError" role="alert" class="text-xs text-rose-300"></p>
                <p class="text-[11px] text-slate-500 pt-1">Trascina il pin sulla mappa per riposizionarlo</p>
                <div class="flex items-center justify-between gap-2 text-xs">
                    <button type="button" @click="togglePinLock(selectedPin)" :disabled="savingPinLock"
                            class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-amber-300 hover:text-amber-200 disabled:opacity-50"
                            :aria-label="selectedPin.locked ? 'Sblocca segnaposto' : 'Blocca segnaposto'"
                            :title="selectedPin.locked ? 'Sblocca segnaposto' : 'Blocca segnaposto'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!selectedPin.locked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0m-2 4h12v10H6V11z"/>
                            <path x-show="selectedPin.locked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4m-2 0h12v10H6V11z"/>
                        </svg>
                        <span x-text="selectedPin.locked ? 'Sblocca' : 'Blocca'"></span>
                    </button>
                    <button type="button" @click="deletePin(selectedPin)" class="shrink-0 whitespace-nowrap text-rose-400 hover:text-rose-300 text-xs" title="Elimina segnaposto">Elimina</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Custom Map Modal -->
    <div x-show="uploadModalOpen" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-cloak>
        <div @click.away="uploadModalOpen = false" class="glass-panel p-6 sm:p-8 rounded-2xl max-w-lg w-full relative">
            <button @click="uploadModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
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
                    <button type="button" @click="uploadModalOpen = false" :disabled="uploadingMap" class="btn-secondary text-xs py-2 px-3 disabled:opacity-50">Annulla</button>
                    <button type="submit" :disabled="uploadingMap" class="btn-primary text-xs py-2 px-4 disabled:opacity-50">
                        <span x-text="uploadingMap ? 'Caricamento… ' + uploadProgress + '%' : 'Carica Mappa'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function customImageMap(config) {
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
        createModalOpen: false,
        detailsModalOpen: false,
        uploadModalOpen: false,
        uploadingMap: false,
        uploadProgress: 0,
        uploadError: '',
        selectedPin: {},
        newPinCoords: { x: 50, y: 50 },
        newPinForm: {
            title: '',
            description: '',
            color: '#e11d48',
            wiki_page_id: ''
        },
        draggingPin: null,
        isDragging: false,
        dragOrigin: null,

        init() {
            this.$nextTick(() => {
                this.mapResizeObserver = new ResizeObserver(() => this.fitMap());
                this.mapResizeObserver.observe(this.$refs.mapViewport);
                this.fitMap();
            });
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
            this.newPinForm = {
                title: '',
                description: '',
                color: '#e11d48',
                wiki_page_id: ''
            };
            this.createModalOpen = true;
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
            this.selectedPin = pin;
            this.mapError = '';
            this.editingPin = false;
            this.detailsModalOpen = true;
        },

        editingPin: false,
        savingPinDetails: false,
        pinEditForm: { title: '', description: '', wiki_page_id: '' },

        startPinEdit() {
            this.pinEditForm = {
                title: this.selectedPin.title,
                description: this.selectedPin.description ?? '',
                wiki_page_id: this.selectedPin.wiki_page_id ? String(this.selectedPin.wiki_page_id) : '',
            };
            this.mapError = '';
            this.editingPin = true;
        },

        async savePinDetails() {
            const pin = this.pins.find((item) => item.id === this.selectedPin.id);
            if (!pin) {
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
                        title: this.pinEditForm.title.trim(),
                        description: this.pinEditForm.description.trim() || null,
                        wiki_page_id: this.pinEditForm.wiki_page_id || null,
                    })
                });
                if (!response.ok) {
                    throw new Error(`Impossibile aggiornare il segnaposto (${response.status}).`);
                }

                const data = await response.json();
                pin.title = data.pin.title;
                pin.description = data.pin.description;
                pin.wiki_page_id = data.pin.wiki_page_id;
                pin.wiki_page_title = data.pin.wiki_page ? data.pin.wiki_page.title : null;
                pin.wiki_page_url = data.pin.wiki_page ? `/campaigns/${config.campaignId}/wiki/${data.pin.wiki_page_id}` : null;
                this.selectedPin = { ...pin };
                this.editingPin = false;
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
                this.selectedPin = { ...pin };
            } catch (error) {
                this.mapError = error.message;
                console.error(error);
            } finally {
                this.savingPinLock = false;
            }
        },

        async saveNewPin() {
            try {
                const payload = {
                    title: this.newPinForm.title,
                    description: this.newPinForm.description,
                    x: this.newPinCoords.x,
                    y: this.newPinCoords.y,
                    color: this.newPinForm.color,
                    campaign_map_id: this.activeMapId,
                    wiki_page_id: this.newPinForm.wiki_page_id || null
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

                if (response.ok) {
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
                        wiki_page_url: data.pin.wiki_page ? `/campaigns/${config.campaignId}/wiki/${data.pin.wiki_page.id}` : null
                    };
                    this.pins.push(newPin);
                    this.createModalOpen = false;
                }
            } catch (err) {
                console.error(err);
            }
        },

        async linkPinToWiki(pin, wikiId) {
            if (!wikiId) return;

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

                if (response.ok) {
                    const data = await response.json();
                    pin.wiki_page_id = parseInt(wikiId);
                    pin.wiki_page_title = data.pin.wiki_page ? data.pin.wiki_page.title : null;
                    pin.wiki_page_url = `/campaigns/${config.campaignId}/wiki/${wikiId}`;
                    this.selectedPin = { ...pin };
                }
            } catch (err) {
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
                    this.detailsModalOpen = false;
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
            } catch (error) {
                pin.x = originalPosition.x;
                pin.y = originalPosition.y;
                this.mapError = error.message;
                this.detailsModalOpen = true;
                this.selectedPin = pin;
                console.error(error);
            }
        }
    }
}
</script>
