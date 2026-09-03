@extends('layouts.pronos')

@section('content')


<div class="mb-4">

    <div class="text-uppercase text-primary fw-bold small">
        PronosTOP14
    </div>

    <h2 class="fw-bold mb-1">
        Nouveautés
    </h2>

    <p class="text-muted mb-0">
        Retrouve ici les 10 dernières nouveautés
        qui t’ont été présentées lors de tes connexions.
    </p>

</div>


@if($announcements->isEmpty())

    <div class="alert alert-info">
        Aucune nouveauté ne t’a encore été présentée.
    </div>

@else

    <div class="row g-4">

        @foreach($announcements as $announcement)

            @php
                $feature =
                    $announcement[
                        'snapshot'
                    ];

                $modalId =
                    'featureHistoryModal'
                    .$announcement[
                        'feature_id'
                    ];
            @endphp


            <div class="col-md-6">

                <div class="rugby-card p-4 h-100 d-flex flex-column">

                    <div class="d-flex align-items-start gap-3">

                        @if(
                            ! empty(
                                $feature['icon']
                                ?? null
                            )
                        )

                            <div class="fs-3">
                                {{ $feature['icon'] }}
                            </div>

                        @endif


                        <div class="flex-grow-1">

                            <h3 class="h5 fw-bold mb-1">
                                {{ $feature['title'] }}
                            </h3>

                            <div class="small text-muted">

                                Présentée le

                                <strong>
                                    {{ $announcement['seen_at']->format('d/m/Y à H:i') }}
                                </strong>

                            </div>

                        </div>

                    </div>


                    @if(
                        ! empty(
                            $feature[
                                'description'
                            ]
                            ?? null
                        )
                    )

                        <p class="text-muted mt-3 mb-4">
                            {{ $feature['description'] }}
                        </p>

                    @endif


                    <div class="mt-auto">

                        <button type="button"
                                class="btn btn-outline-primary rounded-pill fw-bold px-4"
                                data-bs-toggle="modal"
                                data-bs-target="#{{ $modalId }}">
                            Afficher
                        </button>

                    </div>

                </div>

            </div>


            <div class="modal fade"
                 id="{{ $modalId }}"
                 tabindex="-1"
                 aria-labelledby="{{ $modalId }}Label"
                 aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">

                    <div class="modal-content border-0 shadow rounded-4">


                        <div class="modal-header border-0 pb-0">

                            <div>

                                <div class="text-uppercase text-primary fw-bold small mb-1">
                                    Nouveauté
                                </div>

                                <h2 class="modal-title h4 fw-bold mb-0"
                                    id="{{ $modalId }}Label">
                                    ✨ Nouvelle fonctionnalité
                                </h2>

                            </div>


                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer">
                            </button>

                        </div>


                        <div class="modal-body pt-3">

                            @include(
                                'partials.feature-announcement-content',
                                [
                                    'featureIcon' =>
                                        $feature['icon']
                                        ?? null,

                                    'featureTitle' =>
                                        $feature['title']
                                        ?? '',

                                    'featureDescription' =>
                                        $feature['description']
                                        ?? null,

                                    'featureDetails' =>
                                        $feature['details']
                                        ?? [],
                                ]
                            )

                        </div>


                        <div class="modal-footer border-0 pt-0">

                            <button type="button"
                                    class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                                    data-bs-dismiss="modal">
                                Fermer
                            </button>


                            @if(
                                filled(
                                    $feature[
                                        'action_label'
                                    ]
                                    ?? null
                                )
                                && filled(
                                    $feature[
                                        'action_url'
                                    ]
                                    ?? null
                                )
                            )

                                <a href="{{ $feature['action_url'] }}"
                                   class="btn btn-warning rounded-pill fw-bold px-4">
                                    {{ $feature['action_label'] }}
                                </a>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

@endif

@endsection
