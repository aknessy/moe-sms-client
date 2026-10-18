const DROPDOWN_SELECTOR = '.dropdown-menu';

const triggerFor = (dropdown) => document.getElementById(dropdown.getAttribute('aria-labelledby'));

const setDropDownState = function (dropdown, open) {
    dropdown.classList.toggle('hidden', !open);

    const trigger = triggerFor(dropdown);
    if (trigger) {
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
};

const closeAllDropDownMenus = function (except = null) {
    document.querySelectorAll(DROPDOWN_SELECTOR).forEach((dropdown) => {
        if (dropdown !== except) {
            setDropDownState(dropdown, false);
        }
    });
};

window.toggleDropDownMenu = function (item) {
    const dropdown = document.querySelector(item);
    if (!dropdown) return;

    const open = dropdown.classList.contains('hidden');

    closeAllDropDownMenus(dropdown);
    setDropDownState(dropdown, open);
};

// Clicking anywhere else on the page closes an open menu. The trigger itself is
// skipped so its own onclick keeps doing the toggling.
document.addEventListener('click', function (event) {
    document.querySelectorAll(`${DROPDOWN_SELECTOR}:not(.hidden)`).forEach((dropdown) => {
        const trigger = triggerFor(dropdown);
        if (trigger && trigger.contains(event.target)) return;

        setDropDownState(dropdown, false);
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeAllDropDownMenus();
    }
});
