@auth

    @php
        $featureAnnouncements =
            collect(
                session(
                    'login_feature_announcements',
                    []
                )
            );

        $actionFeatures =
            $featureAnnouncements
                ->filter(
                    fn ($feature) =>
                        filled(
                            $feature['action_label']
                            ?? null
                        )
                        && filled(
                            $feature['action_url']
                            ?? null
                        )
                );
    @endphp


    @if($featureAnnouncements->isNotEmpty())

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


                    <div class="modal-header border-0 pb-0">

                        <div>

                            <div class="text-uppercase text-success fw-bold small mb-1">
                                Connexion réussie
                            </div>

                            <h2 class="modal-title h4 fw-bold mb-0"
                                id="featureAnnouncementsModalLabel">

                                @if($featureAnnouncements->count() > 1)
                                    ✨ Nouvelles fonctionnalités
                                @else
                                    ✨ Nouvelle fonctionnalité
                                @endif

                            </h2>

                        </div>

                    </div>


                    <div class="modal-body pt-3">

                        @foreach($featureAnnouncements as $feature)

                            @if(! $loop->first)

                                <hr class="my-4">

                            @endif


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

                        @endforeach

                    </div>


                    <div class="modal-footer border-0 pt-0">

                        <button type="button"
                                class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                                data-bs-dismiss="modal">
                            J’ai compris
                        </button>


                        @foreach($actionFeatures as $feature)

                            <a href="{{ $feature['action_url'] }}"
                               class="btn btn-warning rounded-pill fw-bold px-4">
                                {{ $feature['action_label'] }}
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
