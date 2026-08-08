import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import Sortable from 'sortablejs';

window.Sortable = Sortable;

import 'bootstrap';

const autocompleteIgnoredInputTypes = new Set([
    'button',
    'checkbox',
    'color',
    'file',
    'hidden',
    'image',
    'radio',
    'range',
    'reset',
    'submit',
]);

function autocompleteIsAllowed(element) {
    return element.hasAttribute('data-allow-autocomplete')
        || element.closest('[data-allow-autocomplete]') !== null;
}

function disableFormAutocomplete(root = document) {
    const forms = root instanceof HTMLFormElement
        ? [root]
        : Array.from(root.querySelectorAll?.('form') ?? []);

    forms.forEach(function (form) {
        if (autocompleteIsAllowed(form)) {
            return;
        }

        form.setAttribute('autocomplete', 'off');
    });

    const controls = root.matches?.('input, textarea, select')
        ? [root]
        : Array.from(root.querySelectorAll?.('input, textarea, select') ?? []);

    controls.forEach(function (control) {
        if (autocompleteIsAllowed(control)) {
            return;
        }

        if (
            control instanceof HTMLInputElement
            && autocompleteIgnoredInputTypes.has(control.type)
        ) {
            return;
        }

        control.setAttribute('autocomplete', 'off');

        control.setAttribute('data-lpignore', 'true');
        control.setAttribute('data-1p-ignore', 'true');
        control.setAttribute('data-bwignore', 'true');
    });
}

disableFormAutocomplete(document);

document.addEventListener('DOMContentLoaded', function () {
    disableFormAutocomplete(document);
});

window.addEventListener('pageshow', function () {
    disableFormAutocomplete(document);
});

const autocompleteObserver = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
            if (! (node instanceof Element)) {
                return;
            }

            disableFormAutocomplete(node);
        });
    });
});

autocompleteObserver.observe(document.documentElement, {
    childList: true,
    subtree: true,
});
