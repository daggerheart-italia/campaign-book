@props([
    'icon',
    'label',
    'iconClass' => '',
])

<span class="inline-flex items-center gap-2">
    <x-app-icon :name="$icon" :class="$iconClass" />
    <span class="icon-label-mobile" data-tooltip="{{ $label }}">{{ $label }}</span>
</span>
