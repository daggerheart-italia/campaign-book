<div x-data="publicImageMap({{ Js::from(['maps' => $maps, 'pins' => $pins]) }})" class="space-y-4">
    <template x-if="maps.length === 0">
        <div class="glass-panel p-8 rounded-xl text-center max-w-md mx-auto my-6 border-dashed border-white/20">
            <h4 class="font-['Cinzel'] font-bold text-white text-base mb-1">Nessuna mappa disponibile</h4>
        </div>
    </template>

    <div x-show="maps.length > 0" class="space-y-4">
        <div class="space-y-4">
            <div class="glass-panel flex items-center gap-2 rounded-xl p-2 sm:p-3">
                <x-map-selector />
            </div>

            <div class="glass-panel p-2 rounded-xl flex flex-wrap items-center justify-between gap-2">
                <button @click="showLabels = !showLabels" :aria-pressed="showLabels"
                        :class="showLabels ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-white/5 text-slate-400 border-white/5'"
                        class="btn-secondary !py-1.5 !px-3 text-xs border">
                    <x-icon-label icon="tag" label="Etichette" icon-class="w-3.5 h-3.5" />
                </button>

                <div class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-black/40 p-0.5" role="group" aria-label="Controlli zoom mappa">
                    <button type="button" @click="zoomOut" :disabled="zoomLevel <= 50"
                            class="min-w-8 rounded px-2 py-1.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                        <x-icon-label icon="zoom-out" label="Riduci zoom" />
                    </button>
                    <span class="min-w-12 px-1 text-center text-xs font-mono text-slate-200" x-text="(Math.round(zoomLevel * 10) / 10) + '%'"></span>
                    <button type="button" @click="zoomIn" :disabled="zoomLevel >= 300"
                            class="min-w-8 rounded px-2 py-1.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                        <x-icon-label icon="zoom-in" label="Aumenta zoom" />
                    </button>
                    <button type="button" @click="resetZoom" :disabled="zoomLevel === 100"
                            class="rounded px-2 py-1.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                        <x-icon-label icon="scan" label="Adatta" />
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-3 py-2 text-xs text-slate-300">
                Trascina per spostare la mappa • Pizzica per zoomare su mobile, Ctrl + rotella su desktop • Tocca un pin per i dettagli
            </div>

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
                    <div class="relative m-auto shrink-0 transition-none" :style="mapCanvasStyle">
                        <img x-ref="mapImage" :src="activeMap?.image_url" @load="fitMap" alt="Mappa di Campagna"
                             class="block h-full w-full rounded-lg object-fill shadow-2xl" draggable="false" />

                        <template x-for="pin in currentPins" :key="pin.id">
                            <div data-map-pin
                                 class="absolute -translate-x-1/2 -translate-y-full cursor-pointer group z-30"
                                 :style="'left: ' + pin.x + '%; top: ' + pin.y + '%;'"
                                 @click.stop="openPin(pin)">
                                <div x-show="showLabels"
                                     class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-0.5 rounded-md bg-black/85 backdrop-blur-md border border-white/15 text-[11px] font-bold text-white shadow-lg whitespace-nowrap pointer-events-none tracking-wide">
                                    <span x-text="pin.title"></span>
                                </div>
                                <div class="relative flex flex-col items-center">
                                    <div class="h-5 w-5 rounded-full flex items-center justify-center text-white border-2 border-white shadow-xl transition-transform group-hover:scale-125"
                                         :style="'background-color: ' + pin.color + '; box-shadow: 0 0 12px ' + pin.color + ', 0 4px 8px rgba(0,0,0,0.6);'">
                                        <x-app-icon name="map-pin" class="h-2.5 w-2.5" />
                                    </div>
                                    <div class="w-0 h-0 border-x-[3px] border-x-transparent border-t-[5px] -mt-[1px]" :style="'border-top-color: ' + pin.color + ';'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-4 rounded-xl">
                <h3 class="font-['Cinzel'] text-sm font-bold text-white mb-3">Luoghi Segnati (<span x-text="currentPins.length"></span>)</h3>
                <p x-show="currentPins.length === 0" class="text-xs text-slate-400 text-center py-4">Nessun punto segnato su questa mappa.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    <template x-for="pin in currentPins" :key="pin.id">
                        <button @click="openPin(pin)" class="bg-white/[0.03] hover:bg-white/[0.07] border border-white/[0.07] p-2.5 rounded-lg flex items-center gap-2.5 text-left transition-colors min-w-0">
                            <span class="w-3 h-3 rounded-full shrink-0" :style="'background-color: ' + pin.color"></span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-slate-200 truncate" x-text="pin.title"></span>
                                <span class="block text-[10px] text-slate-400 truncate" x-text="pin.description || ''"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div x-show="detailsOpen" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="detailsOpen = false" class="glass-panel p-6 rounded-2xl max-w-md w-full relative">
            <button @click="detailsOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-white p-1" aria-label="Chiudi">
                <x-app-icon name="x" class="w-5 h-5" />
            </button>
            <div class="flex items-center gap-2.5 mb-2 pr-8">
                <span class="w-4 h-4 rounded-full shrink-0" :style="'background-color: ' + selectedPin.color"></span>
                <h3 class="font-['Cinzel'] text-xl font-bold text-white min-w-0 break-words" x-text="selectedPin.title"></h3>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed mb-4 whitespace-pre-wrap" x-text="selectedPin.description || 'Nessuna descrizione specificata.'"></p>
            <template x-if="selectedPin.wiki_page_url">
                <a :href="selectedPin.wiki_page_url" class="btn-primary w-full text-xs py-2.5 justify-center shadow-lg">
                    <span x-text="'Leggi Scheda Wiki: ' + (selectedPin.wiki_page_title || 'Visualizza')"></span>
                </a>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function publicImageMap(config) {
    return {
        maps: config.maps,
        pins: config.pins,
        activeMapId: config.maps[0]?.id ?? null,
        showLabels: true,
        zoomLevel: 100,
        mapFitWidth: 0,
        mapFitHeight: 0,
        isPanning: false,
        mapTouchMode: null,
        mapTouchStart: null,
        pinchStartDistance: 0,
        pinchStartZoom: 100,
        detailsOpen: false,
        selectedPin: {},

        init() {
            this.$nextTick(() => {
                this.mapResizeObserver = new ResizeObserver(() => this.fitMap());
                this.mapResizeObserver.observe(this.$refs.mapViewport);
                this.fitMap();
            });
        },

        destroy() {
            this.mapResizeObserver?.disconnect();
        },

        get activeMap() {
            return this.maps.find(map => map.id === this.activeMapId) || null;
        },

        get currentPins() {
            return this.getPinsForMap(this.activeMapId);
        },

        get mapCanvasStyle() {
            const zoom = this.zoomLevel / 100;
            return `width: ${this.mapFitWidth * zoom}px; height: ${this.mapFitHeight * zoom}px;`;
        },

        getPinsForMap(mapId) {
            return this.pins.filter(pin => pin.campaign_map_id === mapId);
        },

        selectMap(mapId) {
            this.activeMapId = mapId;
            this.resetZoom();
            this.$nextTick(() => this.fitMap());
        },

        openPin(pin) {
            this.selectedPin = pin;
            this.detailsOpen = true;
        },

        fitMap() {
            const image = this.$refs.mapImage;
            const viewport = this.$refs.mapViewport;
            if (!image?.naturalWidth || !image?.naturalHeight || !viewport?.clientWidth || !viewport?.clientHeight) {
                return;
            }

            const fitScale = Math.min(viewport.clientWidth / image.naturalWidth, viewport.clientHeight / image.naturalHeight);
            this.mapFitWidth = image.naturalWidth * fitScale;
            this.mapFitHeight = image.naturalHeight * fitScale;
        },

        zoomIn() {
            this.zoomLevel = Math.min(300, this.zoomLevel + 25);
        },

        zoomOut() {
            this.zoomLevel = Math.max(50, this.zoomLevel - 25);
        },

        resetZoom() {
            this.zoomLevel = 100;
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

                viewport.scrollLeft = Math.max(0, imageLeft + imagePointX * imageWidth - (clientX - viewportRect.left));
                viewport.scrollTop = Math.max(0, imageTop + imagePointY * imageHeight - (clientY - viewportRect.top));
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
                const [first, second] = event.touches;
                this.mapTouchMode = 'pinch';
                this.pinchStartDistance = Math.hypot(second.clientX - first.clientX, second.clientY - first.clientY);
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
                const [first, second] = event.touches;
                const distance = Math.hypot(second.clientX - first.clientX, second.clientY - first.clientY);

                if (this.mapTouchMode !== 'pinch') {
                    this.mapTouchMode = 'pinch';
                    this.pinchStartDistance = distance;
                    this.pinchStartZoom = this.zoomLevel;
                }

                if (this.pinchStartDistance > 0) {
                    this.setZoomAt(
                        this.pinchStartZoom * distance / this.pinchStartDistance,
                        (first.clientX + second.clientX) / 2,
                        (first.clientY + second.clientY) / 2
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
                this.pinchStartDistance = 0;
            }

            if (event.touches.length === 0 || this.mapTouchMode === 'pinch') {
                this.mapTouchMode = null;
                this.mapTouchStart = null;
                this.isPanning = false;
            }
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
            this.isPanning = true;
            event.preventDefault();

            const onMouseMove = (moveEvent) => {
                viewport.scrollLeft = startScrollLeft - (moveEvent.clientX - startX);
                viewport.scrollTop = startScrollTop - (moveEvent.clientY - startY);
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                this.isPanning = false;
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },
    };
}
</script>
@endpush
