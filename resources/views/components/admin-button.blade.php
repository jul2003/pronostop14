@props([
    'color' => 'yellow',
    'type' => 'button',
])

@php
$classes = match($color) {
    'red' => 'btn btn-danger rounded-pill fw-bold',
    'green' => 'btn btn-success rounded-pill fw-bold',
    'blue' => 'btn btn-primary rounded-pill fw-bold',
    default => 'rugby-btn',
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge([
    'class' => $classes
]) }}>
    {{ $slot }}
</button>
