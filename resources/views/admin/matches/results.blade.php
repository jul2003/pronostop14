                '.js-actual-result-input, '
                + '.js-actual-tries-input, '
                + '.js-actual-home-bonus-input, '
                + '.js-actual-away-bonus-input'
            )
            .forEach(function (input) {
                input.addEventListener('input', function () {
                    updateClearResultButton(input.dataset.matchId);
                });

                input.addEventListener('change', function () {
                    updateClearResultButton(input.dataset.matchId);
                    updateCompletedMatchesCount();
                });
            });

        document
            .querySelectorAll('.js-clear-result-button')
            .forEach(function (button) {
                updateClearResultButton(button.dataset.matchId);

                button.addEventListener('click', function () {
                    const matchId = button.dataset.matchId;

                    document.querySelectorAll(
                        '.js-actual-result-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    document.querySelectorAll(
                        '.js-actual-home-bonus-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    document.querySelectorAll(
                        '.js-actual-away-bonus-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    const triesInput = document.querySelector(
                        '.js-actual-tries-input[data-match-id="'
                            + matchId
                            + '"]'
                    );

                    if (triesInput && !triesInput.disabled) {
                        triesInput.value = '';
                    }

                    updateClearResultButton(matchId);
                    updateCompletedMatchesCount();
                });
            });

        updateCompletedMatchesCount();

        document
            .querySelectorAll('.js-clear-exception-deadline-button')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    const matchId = button.dataset.matchId;

                    const input = document.querySelector(
                        '.js-exception-deadline-input[data-match-id="'
                            + matchId
                            + '"]'
                    );

                    if (!input || input.disabled) {
                        return;
                    }

                    input.value = '';
                    input.focus();
                });
            });

        const automaticActionsModalElement = document.getElementById(
            'automaticActionsModal'
        );

        const automaticActionsOpenButton = document.getElementById(
            'automaticActionsOpenButton'
        );

        function openAutomaticActionsModal(attempt) {
            if (!automaticActionsModalElement) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal
                    .getOrCreateInstance(automaticActionsModalElement)
                    .show();

                return;
            }

            if (automaticActionsOpenButton) {
                automaticActionsOpenButton.click();
            }

            if (
                !automaticActionsModalElement.classList.contains('show')
                && attempt < 20
            ) {
                window.setTimeout(function () {
                    openAutomaticActionsModal(attempt + 1);
                }, 150);
            }
        }

        if (automaticActionsModalElement) {
            window.setTimeout(function () {
                openAutomaticActionsModal(0);
            }, 150);
        }
    });
</script>
