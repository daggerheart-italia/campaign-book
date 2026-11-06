@props(['name'])

@php
    $attributes = $attributes->merge([
        'class' => 'w-4 h-4',
        'aria-hidden' => 'true',
        'focusable' => 'false',
    ]);
@endphp

{{ svg('lucide-' . $name, $attributes->get('class'), $attributes->except('class')->all()) }}
