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
        'wiki_page_id' => $p->wiki_page_id,
        'wiki_page_title' => $p->wikiPage ? $p->wikiPage->title : null,
        'wiki_page_url' => $p->wikiPage ? route('wiki.show', [$campaign, $p->wikiPage]) : null,
    ])) }},
    storePinUrl: '{{ route('pins.store', $campaign) }}',
    updatePinBaseUrl: '{{ url("/campaigns/{$campaign->id}/pins") }}',
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
            <button @click="showLabels = !showLabels" 
                    :class="showLabels ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 border-white/5'"
                    class="btn-secondary !py-1.5 !px-3 text-xs border" title="Mostra o nascondi le etichette con nome">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span class="hidden sm:inline">Etichette</span>
            </button>

            <!-- Zoom Controls -->
            <div class="hidden sm:flex items-center gap-1 bg-black/40 rounded-lg p-0.5 border border-white/10">
                <button @click="zoomOut" class="p-1.5 text-slate-400 hover:text-white hover:bg-white/10 rounded" title="Riduci">-</button>
                <span class="text-[11px] font-mono px-1.5 text-slate-300" x-text="zoomLevel + '%'"></span>
                <button @click="zoomIn" class="p-1.5 text-slate-400 hover:text-white hover:bg-white/10 rounded" title="Ingrandisci">+</button>
                <button @click="resetZoom" class="text-[10px] text-slate-500 hover:text-slate-300 px-1.5" title="Reimposta zoom">100%</button>
            </div>

            <!-- Upload New Map Button -->
            <button @click="uploadModalOpen = true" class="btn-primary text-xs py-1.5 px-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Carica Mappa</span>
            </button>
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

    <!-- Interactive Map Stage (Custom Image with Pins) -->
    <div class="glass-panel p-2 sm:p-3 rounded-2xl relative overflow-hidden">
        <!-- Helper notice -->
        <div class="absolute top-4 left-4 z-20 pointer-events-none">
            <div class="bg-black/80 backdrop-blur-md border border-white/10 px-3 py-1.5 rounded-lg text-[11px] text-slate-300 flex items-center gap-2 shadow-lg">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Clicca sull'immagine per piazzare un pin • Trascina per spostarlo</span>
            </div>
        </div>

        <!-- Scrollable / Zoomable Wrapper -->
        <div class="w-full overflow-auto max-h-[75vh] rounded-xl border border-white/10 bg-[#070a12] scrollbar-thin select-none"
             id="map-viewport">
            <div class="relative transition-all duration-150 origin-top-left"
                 :style="'width: ' + zoomLevel + '%; min-width: 100%; min-height: 400px;'">

                <!-- Map Image -->
                <img :src="currentMapUrl" 
                     @click="handleMapClick($event)"
                     id="map-image-element"
                     alt="Mappa di Campagna"
                     class="w-full h-auto block rounded-lg cursor-crosshair shadow-2xl"
                     draggable="false" />

                <!-- Clickable Pins Overlay -->
                <template x-for="pin in currentPins" :key="pin.id">
                    <div class="absolute -translate-x-1/2 -translate-y-full cursor-pointer group z-30 transition-transform duration-75"
                         :style="'left: ' + pin.x + '%; top: ' + pin.y + '%;'"
                         @mousedown="startDrag($event, pin)"
                         @touchstart="startTouchDrag($event, pin)"
                         @click.stop="openPinPopover(pin, $event)">
                        
                        <!-- Visible Label directly over/below pin -->
                        <div x-show="showLabels"
                             class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-0.5 rounded-md bg-black/85 backdrop-blur-md border border-white/15 text-[11px] font-bold text-white shadow-lg whitespace-nowrap pointer-events-none tracking-wide">
                            <span x-text="pin.title"></span>
                        </div>

                        <!-- Pin Marker Icon -->
                        <div class="relative flex flex-col items-center">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white border-2 border-white shadow-xl transition-transform group-hover:scale-125"
                                 :style="'background-color: ' + pin.color + '; box-shadow: 0 0 12px ' + pin.color + ', 0 4px 8px rgba(0,0,0,0.6);'">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <!-- Pin Needle Point -->
                            <div class="w-0 h-0 border-x-4 border-x-transparent border-t-6 -mt-[1px]"
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
         style="display: none;">
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
         style="display: none;">
        <div @click.away="detailsModalOpen = false" class="glass-panel p-6 rounded-2xl max-w-md w-full relative">
            <button @click="detailsModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-4 h-4 rounded-full" :style="'background-color: ' + selectedPin.color"></span>
                <h3 class="font-['Cinzel'] text-xl font-bold text-white" x-text="selectedPin.title"></h3>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed mb-4 whitespace-pre-wrap" x-text="selectedPin.description || 'Nessuna descrizione specificata.'"></p>

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

                <div class="flex items-center justify-between text-xs pt-1">
                    <span class="text-[11px] text-slate-500">Trascina il pin sulla mappa per riposizionarlo</span>
                    <button type="button" @click="deletePin(selectedPin)" class="text-rose-400 hover:text-rose-300 text-xs">Elimina Segnaposto</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Custom Map Modal -->
    <div x-show="uploadModalOpen" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         style="display: none;">
        <div @click.away="uploadModalOpen = false" class="glass-panel p-6 sm:p-8 rounded-2xl max-w-lg w-full relative">
            <button @click="uploadModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-['Cinzel'] text-xl font-bold text-white mb-1">Carica Mappa di Gioco</h3>
                <p class="text-xs text-slate-400">Carica una mappa geografica, mappa regionale o piantina di un dungeon (PNG, JPG, WebP fino a 15MB).</p>
            </div>

            <form method="POST" action="{{ route('maps.store', $campaign) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">Titolo della Mappa *</label>
                    <input type="text" name="title" required class="input-premium text-sm font-semibold" placeholder="es. Il Continente d'Ambra, I Sotterranei di Kar-Drak" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">File Immagine *</label>
                    <input type="file" name="image" required accept="image/*" class="input-premium text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="uploadModalOpen = false" class="btn-secondary text-xs py-2 px-3">Annulla</button>
                    <button type="submit" class="btn-primary text-xs py-2 px-4">Carica Mappa</button>
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
        createModalOpen: false,
        detailsModalOpen: false,
        uploadModalOpen: false,
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
        },

        zoomIn() {
            if (this.zoomLevel < 250) this.zoomLevel += 25;
        },

        zoomOut() {
            if (this.zoomLevel > 100) this.zoomLevel -= 25;
        },

        resetZoom() {
            this.zoomLevel = 100;
        },

        handleMapClick(event) {
            if (this.isDragging) return;

            const rect = event.target.getBoundingClientRect();
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

        openPinPopover(pin, event) {
            if (this.isDragging) return;
            this.selectedPin = pin;
            this.detailsModalOpen = true;
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
            if (event.button !== 0) return; // left click only
            this.draggingPin = pin;
            this.isDragging = false;

            const mapImg = document.getElementById('map-image-element');
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
                    this.savePinCoordinates(this.draggingPin);
                    setTimeout(() => { this.isDragging = false; }, 50);
                }
                this.draggingPin = null;
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        // Drag and Drop (Touch for mobile)
        startTouchDrag(event, pin) {
            this.draggingPin = pin;
            this.isDragging = false;

            const mapImg = document.getElementById('map-image-element');
            if (!mapImg) return;

            const onTouchMove = (moveEvent) => {
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
                    this.savePinCoordinates(this.draggingPin);
                    setTimeout(() => { this.isDragging = false; }, 100);
                }
                this.draggingPin = null;
            };

            document.addEventListener('touchmove', onTouchMove, { passive: true });
            document.addEventListener('touchend', onTouchEnd);
        },

        async savePinCoordinates(pin) {
            try {
                await fetch(`${config.updatePinBaseUrl}/${pin.id}`, {
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
            } catch (err) {
                console.error(err);
            }
        }
    }
}
</script>
