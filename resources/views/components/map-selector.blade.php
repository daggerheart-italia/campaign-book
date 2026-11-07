<div class="relative min-w-0 flex-1">
    <x-app-icon name="map" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-400" />
    <select aria-label="Mappa visualizzata"
            x-model.number="activeMapId"
            @change="selectMap(Number($event.target.value))"
            class="min-h-11 w-full appearance-none rounded-lg border border-amber-500/30 bg-[#151b27] py-2 pr-9 pl-10 text-sm font-semibold text-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 forced-colors:appearance-auto">
        <template x-for="map in maps" :key="map.id">
            <option :value="map.id" x-text="map.title + (map.is_default ? ' · Predefinita' : '') + ' · ' + getPinsForMap(map.id).length + ' pin'"></option>
        </template>
    </select>
    <x-app-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400 forced-colors:hidden" />
</div>
