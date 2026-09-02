<hr class="my-4">

<div id="notifications-email"
     class="scroll-target">

    @if($showNotificationFeaturesAnnouncement ?? false)
        <div class="alert alert-warning border-warning mb-4">
            <div class="d-flex gap-3 align-items-start">
                <div class="fs-3">
                    ✨
                </div>

                <div>
                    <div class="text-uppercase fw-bold small mb-1">
                        Nouvelle fonctionnalité
                    </div>

                    <h2 class="h5 fw-bold mb-2">
                        Notifications par email
                    </h2>

                    <p class="mb-2">
                        PronosTOP14 peut maintenant t’avertir
                        lorsqu’un nouveau pronostic est disponible,
                        lorsque tous les résultats d’une journée
                        sont connus et lorsqu’un prono incomplet
                        approche de sa date limite.
                    </p>

                    <p class="mb-0">
                        <strong>
                            Aucune notification n’est activée automatiquement.
                        </strong>

                        Choisis ci-dessous uniquement celles
                        que tu souhaites recevoir.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-4">
        <h2 class="h5 fw-bold mb-1">
            Notifications par email
        </h2>

        <p class="text-muted mb-0">
            Choisis les notifications que tu souhaites recevoir.
            Elles sont toutes désactivées par défaut.
        </p>
    </div>

    <div class="border rounded-4 p-3 p-md-4">

        <input type="hidden"
               name="notify_new_prediction"
               value="0">

        <div class="form-check form-switch mb-4">
            <input type="checkbox"
                   name="notify_new_prediction"
                   id="notify_new_prediction"
                   value="1"
                   class="form-check-input"
                   @checked(
                       (bool) old(
                           'notify_new_prediction',
                           $user->notify_new_prediction
                       )
                   )>

            <label for="notify_new_prediction"
                   class="form-check-label">

                <span class="fw-bold d-block">
                    Nouveau prono disponible
                </span>

                <span class="text-muted small">
                    Recevoir un email lorsqu’une nouvelle
                    journée de TOP 14 est prête
                    à être pronostiquée.
                </span>
            </label>
        </div>

        <input type="hidden"
               name="notify_results_available"
               value="0">

        <div class="form-check form-switch mb-4">
            <input type="checkbox"
                   name="notify_results_available"
                   id="notify_results_available"
                   value="1"
                   class="form-check-input"
                   @checked(
                       (bool) old(
                           'notify_results_available',
                           $user->notify_results_available
                       )
                   )>

            <label for="notify_results_available"
                   class="form-check-label">

                <span class="fw-bold d-block">
                    Résultats disponibles
                </span>

                <span class="text-muted small">
                    Recevoir un email lorsque tous
                    les résultats d’une journée
                    sont enregistrés.
                </span>
            </label>
        </div>

        <input type="hidden"
               name="notify_prediction_reminder"
               value="0">

        <div class="form-check form-switch">
            <input type="checkbox"
                   name="notify_prediction_reminder"
                   id="notify_prediction_reminder"
                   value="1"
                   class="form-check-input"
                   @checked(
                       (bool) old(
                           'notify_prediction_reminder',
                           $user->notify_prediction_reminder
                       )
                   )>

            <label for="notify_prediction_reminder"
                   class="form-check-label">

                <span class="fw-bold d-block">
                    Rappel avant clôture
                </span>

                <span class="text-muted small">
                    Recevoir un rappel lorsqu’un
                    pronostic n’est pas complètement
                    renseigné à l’approche de sa
                    date limite.

                    Le délai est défini par
                    l’administrateur.
                </span>
            </label>
        </div>
    </div>
</div>

<hr class="my-4">
