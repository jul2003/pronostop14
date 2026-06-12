<div class="mb-4">
    @isset($back)
        <a href="{{ $back }}" class="text-decoration-none fw-bold text-primary">
            ← Retour
        </a>
    @endisset

    <h2 class="mt-3 fw-bold text-dark">
        {{ $title }}
    </h2>
</div>
