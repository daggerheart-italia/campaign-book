@props(['parentPages', 'selected' => null, 'restoreRevision' => null, 'name' => 'parent_id', 'label' => 'Scheda genitore', 'modelable' => null])

@php
    $options = $parentPages->map(fn ($page) => [
        'id' => $page->id,
        'label' => ($page->category ?: 'Generale') . ' — ' . $page->title,
    ])->values()->toArray();

    $initialSelected = old($name, $selected);
    $id = $name;
@endphp

<div x-data="parentPageSelect({{ json_encode(['options' => $options, 'selected' => (string) $initialSelected]) }})"
     @click.outside="closeDropdown()"
     @keydown.escape.window="closeDropdown()"
     class="relative">

    <label for="{{ $id }}" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">{{ $label }}</label>

    <div class="relative">
        <input type="text"
               x-model="query"
               @input="onInput()"
               @keydown="onKeyDown($event)"
               @focus="openDropdown()"
               placeholder="Cerca scheda…"
               class="input-premium text-sm w-full"
               autocomplete="off"
               aria-autocomplete="list"
               aria-controls="{{ $id }}_listbox"
               aria-expanded="false"
               x-bind:aria-expanded="open"
               x-bind:aria-owns="open ? '{{ $id }}_listbox' : null"
               x-bind:aria-activedescendant="open && highlight >= 0 ? '{{ $id }}_option_' + highlight : null" />

        <button type="button"
                @click="toggleDropdown()"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-amber-400 transition-colors"
                :aria-expanded="open"
                aria-label="Apri/chiudi elenco">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-transition>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <input type="hidden"
               name="{{ $name }}"
               id="{{ $id }}"
               x-model="selected"
               @if($modelable) x-modelable="{{ $modelable }}" @endif
               value="{{ $initialSelected }}"
               class="sr-only" />
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.stop="closeDropdown()"
         class="absolute z-50 mt-1 w-full min-w-[16rem] max-h-60 overflow-y-auto rounded-xl border border-white/10 bg-[#0b1120] shadow-2xl shadow-black/50"
         id="{{ $id }}_listbox"
         role="listbox"
         ref="list"
         x-cloak>

        <template x-if="!hasResults">
            <div class="px-3 py-2 text-xs text-slate-400 italic">
                Nessuna scheda corrisponde alla ricerca.
            </div>
        </template>

        <template x-for="(option, index) in filtered" :key="option.id">
            <div :id="'{{ $id }}_option_' + index"
                 @click="select(option)"
                 @mouseenter="highlight = index"
                 :class="{
                     'bg-amber-500/15 text-amber-200': highlight === index,
                     'text-slate-200': highlight !== index
                 }"
                 class="px-3 py-2 text-sm cursor-pointer border-b border-white/[0.04] last:border-b-0 hover:bg-white/[0.05] transition-colors flex items-center justify-between gap-2"
                 role="option"
                 x-bind:aria-selected="highlight === index">
                <span x-text="option.label" class="truncate"></span>
                <span x-show="String(selected) === String(option.id)"
                      class="shrink-0 text-amber-400"
                      x-cloak>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </div>
        </template>

        <div class="px-3 py-1.5 text-[11px] text-slate-500 border-t border-white/[0.04] flex items-center gap-3">
            <span>↑↓</span>
            <span>Sposta</span>
            <span>ENTER</span>
            <span>Seleziona</span>
            <span>ESC</span>
            <span>Chiudi</span>
        </div>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
    @endif
</div>