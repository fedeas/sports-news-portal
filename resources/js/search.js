// The search bar in the masthead. Without this script the search icon is a plain link to the
// search page; with it, the icon opens a bar that suggests stories and clubs as the reader types.
// Suggestions are built with textContent only, never as HTML.
const DEBOUNCE_MS = 150;

const root = document.querySelector('[data-search]');

if (root) {
    enhance(root);
}

function enhance(root) {
    const openLink = root.querySelector('[data-search-open]');
    const panel = root.querySelector('[data-search-panel]');
    const closeButton = root.querySelector('[data-search-close]');
    const input = panel.querySelector('input');
    const listbox = root.querySelector('[data-search-suggestions]');
    const status = root.querySelector('[data-search-status]');
    const endpoint = root.dataset.searchEndpoint;
    const minLength = Number(root.dataset.searchMinLength);
    const labels = root.dataset;

    let timer = null;
    let request = null;
    let options = [];
    let active = -1;

    const open = () => {
        panel.hidden = false;
        openLink.setAttribute('aria-expanded', 'true');
        input.focus();
    };

    const close = ({ restoreFocus = true } = {}) => {
        hideSuggestions();
        panel.hidden = true;
        openLink.setAttribute('aria-expanded', 'false');

        if (restoreFocus) {
            openLink.focus();
        }
    };

    const hideSuggestions = () => {
        request?.abort();
        listbox.hidden = true;
        listbox.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        options = [];
        active = -1;
    };

    const highlight = (index) => {
        options[active]?.setAttribute('aria-selected', 'false');
        active = index;

        if (active < 0) {
            input.removeAttribute('aria-activedescendant');

            return;
        }

        options[active].setAttribute('aria-selected', 'true');
        options[active].scrollIntoView({ block: 'nearest' });
        input.setAttribute('aria-activedescendant', options[active].id);
    };

    const option = (url, title, meta, image) => {
        const link = document.createElement('a');
        link.href = url;
        link.id = `site-search-option-${options.length}`;
        link.className = 'site-search__option';
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');
        link.tabIndex = -1;

        if (image !== undefined) {
            const thumb = document.createElement(image ? 'img' : 'span');
            thumb.className = 'site-search__thumb';

            if (image) {
                thumb.src = image;
                thumb.alt = '';
                thumb.width = 48;
                thumb.height = 48;
            }

            link.append(thumb);
        }

        const text = document.createElement('span');
        text.className = 'min-w-0';

        const titleElement = document.createElement('span');
        titleElement.className = 'site-search__title';
        titleElement.textContent = title;
        text.append(titleElement);

        if (meta) {
            const metaElement = document.createElement('span');
            metaElement.className = 'site-search__meta';
            metaElement.textContent = meta;
            text.append(metaElement);
        }

        link.append(text);
        options.push(link);

        return link;
    };

    const group = (label, children) => {
        const element = document.createElement('div');
        element.setAttribute('role', 'group');
        element.setAttribute('aria-label', label);

        const heading = document.createElement('div');
        heading.className = 'site-search__group-label';
        heading.setAttribute('aria-hidden', 'true');
        heading.textContent = label;

        element.append(heading, ...children);

        return element;
    };

    const render = (term, { articles, teams }) => {
        listbox.replaceChildren();
        options = [];
        active = -1;

        if (teams.length > 0) {
            listbox.append(group(labels.labelTeams, teams.map((team) => option(team.url, team.name, null))));
        }

        if (articles.length > 0) {
            listbox.append(
                group(
                    labels.labelArticles,
                    articles.map((article) => option(article.url, article.title, `${article.category} · ${article.author}`, article.image)),
                ),
            );
        }

        if (options.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'site-search__note';
            empty.textContent = labels.labelEmpty.replace(':query', term);
            listbox.append(empty);
        }

        // The full results page, for when the suggestions are not enough.
        const all = document.createElement('a');
        all.href = `${panel.querySelector('form').action}?q=${encodeURIComponent(term)}`;
        all.className = 'site-search__all';
        all.textContent = labels.labelAll.replace(':query', term);
        listbox.append(all);

        listbox.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        status.textContent = labels.labelCount.replace(':count', String(options.length));
    };

    const suggest = async () => {
        const term = input.value.trim();

        if (term.length < minLength) {
            hideSuggestions();

            return;
        }

        request?.abort();
        request = new AbortController();

        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });

            // Rate limited or failing: keep quiet, the full search page still works on Enter.
            if (!response.ok) {
                return;
            }

            render(term, await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') {
                hideSuggestions();
            }
        }
    };

    openLink.addEventListener('click', (event) => {
        event.preventDefault();
        open();
    });

    closeButton.addEventListener('click', () => close());

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(suggest, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && options.length > 0) {
            event.preventDefault();
            highlight((active + 1) % options.length);
        } else if (event.key === 'ArrowUp' && options.length > 0) {
            event.preventDefault();
            highlight(active <= 0 ? options.length - 1 : active - 1);
        } else if (event.key === 'Enter' && active >= 0) {
            // A highlighted suggestion wins over submitting the search form.
            event.preventDefault();
            options[active].click();
        } else if (event.key === 'Escape') {
            event.preventDefault();

            if (listbox.hidden) {
                close();
            } else {
                hideSuggestions();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!panel.hidden && !root.contains(event.target)) {
            close({ restoreFocus: false });
        }
    });
}
