@auth
    @if(session('show_notification_features_modal'))

        <button type="button"
                id="notificationFeaturesModalTrigger"
                class="d-none"
                data-bs-toggle="modal"
                data-bs-target="#notificationFeaturesModal"
                aria-hidden="true">
        </button>

        <div class="modal fade"
             id="notificationFeaturesModal"
             tabindex="-1"
             aria-labelledby="notificationFeaturesModalLabel"
             aria-hidden="true"
             data-bs-backdrop="static"
             data-bs-keyboard="false">

            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <div class="text-uppercase text-success fw-bold small mb-1">
                                Connexion réussie
                            </div>

                            <h2 class="modal-title h4 fw-bold mb-0"
                                id="notificationFeaturesModalLabel">
                                ✨ Nouvelle fonctionnalité
                            </h2>
                        </div>
                    </div>

                    <div class="modal-body pt-3">

                        <p class="mb-3">
                            PronosTOP14 peut maintenant t’envoyer
                            des notifications par email.
                        </p>

                        <div class="border rounded-4 p-3 mb-3 bg-light">

                            <div class="mb-3">
                                <div class="fw-bold">
                                    🏉 Nouveau prono disponible
                                </div>

                                <div class="small text-muted">
                                    Un email lorsqu’une nouvelle journée
                                    est prête à être pronostiquée.
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-bold">
                                    🏆 Résultats disponibles
                                </div>

                                <div class="small text-muted">
                                    Un email lorsque tous les résultats
                                    d’une journée sont enregistrés.
                                </div>
                            </div>

                            <div>
                                <div class="fw-bold">
                                    ⏰ Rappel avant clôture
                                </div>

                                <div class="small text-muted">
                                    Un rappel lorsqu’un prono est encore
                                    incomplet à l’approche de sa date limite.
                                </div>
                            </div>

                        </div>

                        <div class="alert alert-info mb-0">
                            <strong>
                                Aucune notification n’est activée automatiquement.
                            </strong>

                            <div class="mt-1">
                                Tu peux choisir librement les notifications
                                que tu souhaites recevoir depuis ton profil.
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <a href="{{ route('player-profile.edit') }}#notifications-email"
                           class="btn btn-warning rounded-pill fw-bold px-4">
                            Découvrir et configurer mes notifications
                        </a>
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
                            'notificationFeaturesModalTrigger'
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
