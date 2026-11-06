@props([
    'icon',
    'label',
    'iconClass' => '',
    'showOnMobile' => false,
    'hideOnDesktop' => false,
])

<span class="inline-flex items-center gap-2" @if($hideOnDesktop) title="{{ $label }}" @endif>
    <x-app-icon :name="$icon" :class="$iconClass" />
    <span @class([
        'icon-label-mobile' => ! $showOnMobile,
        'icon-label-hide-desktop' => ! $showOnMobile && $hideOnDesktop,
        'sm:sr-only' => $showOnMobile && $hideOnDesktop,
    ]) data-tooltip="{{ $label }}">{{ $label }}</span>
</span>
