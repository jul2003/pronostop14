@auth

    @php
        $featureIds = collect(
            session(
                'login_feature_ids',
                []
            )
        )
            ->filter()
            ->unique()
            ->values();

        $features = $featureIds->isEmpty()
            ? collect()
            : \App\Models\Feature::query()
                ->whereIn(
                    'id',
                    $featureIds
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'id'
                )
                ->get();

        $actionFeatures =
            $features
                ->filter(
                    fn ($feature) =>
                        filled(
                            $feature->action_label
                        )
                        && filled(
                            $feature->action_url
                        )
                );
    @endphp


    @if($features->isNotEmpty())

        <button type="button"
                id="featureAnnouncementsModalTrigger"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#featureAnnouncementsModal"
                aria-hidden="true">
        </button>


        <div class="modal fade"
             id="featureAnnouncementsModal"
             tabindex="-1"
             aria-labelledby="featureAnnouncementsModalLabel"
             aria-hidden="true"
             data-bs-backdrop="static"
             data-bs-keyboard="false">

            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">

                <div class="modal-content border-0 shadow rounded-4">


                    {{-- HEADER --}}

                    <div class="modal-header border-0 pb-0">

                        <div>

                            <div class="text-uppercase text-success fw-bold small mb-1">
                                Connexion réussie
                            </div>

                            <h2 class="modal-title h4 fw-bold mb-0"
                                id="featureAnnouncementsModalLabel">

                                @if($features->count() > 1)
                                    ✨ Nouvelles fonctionnalités
                                @else
                                    ✨ Nouvelle fonctionnalité
                                @endif

                            </h2>

                        </div>

                    </div>


                    {{-- BODY --}}

                    <div class="modal-body pt-3">

                        @foreach($features as $feature)

                            @if(! $loop->first)

                                <hr class="my-4">

                            @endif


                            <section>

                                <h3 class="h5 fw-bold mb-2">

                                    @if($feature->icon)
                                        {{ $feature->icon }}
                                    @endif

                                    {{ $feature->title }}

                                </h3>


                                @if($feature->description)

                                    <p class="mb-3">
                                        {{ $feature->description }}
                                    </p>

                                @endif


                                @if(
                                    is_array(
                                        $feature->details
                                    )
                                    && count(
                                        $feature->details
                                    ) > 0
                                )

                                    <div class="border rounded-4 p-3 bg-light">

                                        @foreach(
                                            $feature->details
                                            as $detail
                                        )

                                            <div @class([
                                                'mb-3' =>
                                                    ! $loop->last,
                                            ])>

                                                @if(
                                                    ! empty(
                                                        $detail[
                                                            'title'
                                                        ]
                                                        ?? null
                                                    )
                                                )

                                                    <div class="fw-bold">
                                                        {{ $detail['title'] }}
                                                    </div>

                                                @endif


                                                @if(
                                                    ! empty(
                                                        $detail[
                                                            'text'
                                                        ]
                                                        ?? null
                                                    )
                                                )

                                                    <div class="small text-muted">
                                                        {{ $detail['text'] }}
                                                    </div>

                                                @endif

                                            </div>

                                        @endforeach

                                    </div>

                                @endif

                            </section>

                        @endforeach

                    </div>


                    {{-- FOOTER --}}

                    <div class="modal-footer border-0 pt-0">

                        <button type="button"
                                class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                                data-bs-dismiss="modal">
                            J’ai compris
                        </button>


                        @foreach($actionFeatures as $feature)

                            <a href="{{ $feature->action_url }}"
                               class="btn btn-warning rounded-pill fw-bold px-4">
                                {{ $feature->action_label }}
                            </a>

                        @endforeach

                    </div>

                </div>

            </div>

        </div>


        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const trigger =
                        document.getElementById(
                            'featureAnnouncementsModalTrigger'
                        );

                    if (!trigger) {
                        return;
                    }

                    trigger.click();

                }
            );
        </script>

    @endif

@endauth
