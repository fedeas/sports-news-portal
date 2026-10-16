// The sections drawer is a <details> element, so it opens and closes without JavaScript.
// This only adds what markup alone cannot: closing on Escape, and on a click outside it.
const drawer = () => document.querySelector('[data-sections-menu][open]');

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    const open = drawer();

    if (open) {
        open.removeAttribute('open');
        open.querySelector('summary')?.focus();
    }
});

document.addEventListener('click', (event) => {
    const open = drawer();

    if (open && event.target.closest('[data-sections-menu-backdrop]')) {
        open.removeAttribute('open');
    }
});
