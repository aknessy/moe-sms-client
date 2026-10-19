/**
 * Searchable select, built on a plain text input plus a hidden field that
 * carries the real value. Everything is delegated off the document so pickers
 * inside cloned form rows work without re-initialising anything.
 */
const ROOT = '[data-staff-picker]';
const SEARCH = '[data-picker-search]';
const VALUE = '[data-picker-value]';
const LIST = '[data-picker-list]';
const OPTION = '[data-picker-option]';
const EMPTY = '[data-picker-empty]';

const ACTIVE = ['bg-[#2E7D32]/10', 'text-[#1B5E20]'];

const options = (root, visibleOnly = false) =>
    [...root.querySelectorAll(OPTION)].filter((option) => ! visibleOnly || ! option.parentElement.hidden);

const activeOption = (root) => root.querySelector(`${OPTION}[data-active]`);

function setActive(root, option) {
    const current = activeOption(root);

    if (current) {
        delete current.dataset.active;
        current.classList.remove(...ACTIVE);
    }

    if (option) {
        option.dataset.active = 'true';
        option.classList.add(...ACTIVE);
        option.scrollIntoView({ block: 'nearest' });
    }
}

function filter(root, term) {
    const needle = term.trim().toLowerCase();
    let shown = 0;

    options(root).forEach((option) => {
        const match = option.dataset.search.includes(needle);
        option.parentElement.hidden = ! match;
        if (match) shown += 1;
    });

    root.querySelector(EMPTY).hidden = shown > 0;
    setActive(root, null);
}

function open(root) {
    root.querySelector(LIST).hidden = false;
    root.querySelector(SEARCH).setAttribute('aria-expanded', 'true');
}

function close(root) {
    root.querySelector(LIST).hidden = true;
    root.querySelector(SEARCH).setAttribute('aria-expanded', 'false');
    setActive(root, null);
}

function choose(root, option) {
    root.querySelector(VALUE).value = option.dataset.value;
    root.querySelector(SEARCH).value = option.dataset.label;
    close(root);

    root.querySelector(VALUE).dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Typing something that matches nothing must not leave a stale id behind, so
 * the text is reset to whatever is actually selected when focus leaves.
 */
function reconcile(root) {
    const value = root.querySelector(VALUE);
    const search = root.querySelector(SEARCH);
    const selected = options(root).find((option) => option.dataset.value === value.value);

    if (selected && search.value.trim() === selected.dataset.label) {
        return;
    }

    if (search.value.trim() === '') {
        value.value = '';
        return;
    }

    search.value = selected ? selected.dataset.label : '';
    if (! selected) {
        value.value = '';
    }
}

document.addEventListener('input', (event) => {
    const search = event.target.closest(SEARCH);
    if (! search) return;

    const root = search.closest(ROOT);
    root.querySelector(VALUE).value = '';
    filter(root, search.value);
    open(root);
});

document.addEventListener('focusin', (event) => {
    const search = event.target.closest(SEARCH);
    if (! search) return;

    const root = search.closest(ROOT);
    filter(root, '');
    open(root);
});

// mousedown rather than click: it beats the input's blur, so the pick survives.
document.addEventListener('mousedown', (event) => {
    const option = event.target.closest(OPTION);

    if (option) {
        event.preventDefault();
        choose(option.closest(ROOT), option);
        return;
    }

    document.querySelectorAll(ROOT).forEach((root) => {
        if (! root.contains(event.target)) {
            reconcile(root);
            close(root);
        }
    });
});

document.addEventListener('keydown', (event) => {
    const search = event.target.closest(SEARCH);
    if (! search) return;

    const root = search.closest(ROOT);
    const visible = options(root, true);
    const current = activeOption(root);
    const index = current ? visible.indexOf(current) : -1;

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        open(root);

        if (! visible.length) return;

        const next = event.key === 'ArrowDown'
            ? (index + 1) % visible.length
            : (index <= 0 ? visible.length : index) - 1;

        setActive(root, visible[next]);
        return;
    }

    if (event.key === 'Enter' && ! root.querySelector(LIST).hidden) {
        // Only swallow the key when it is actually picking something.
        const target = current ?? (visible.length === 1 ? visible[0] : null);

        if (target) {
            event.preventDefault();
            choose(root, target);
        }
        return;
    }

    if (event.key === 'Escape') {
        reconcile(root);
        close(root);
    }
});
