@props([
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div class="mb-3">
    <a href="{{ $href }}"
       class="text-decoration-none fw-bold">
        ← {{ $label }}
    </a>
</div>
