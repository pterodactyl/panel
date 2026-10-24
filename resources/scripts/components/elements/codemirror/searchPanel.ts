import {
    closeSearchPanel,
    findNext,
    findPrevious,
    getSearchQuery,
    replaceAll,
    replaceNext,
    SearchQuery,
    selectMatches,
    setSearchQuery,
} from '@codemirror/search';
import { EditorView, type Panel, type ViewUpdate } from '@codemirror/view';

export class CustomSearchPanel implements Panel {
    dom: HTMLElement;
    top = true;
    searchField: HTMLInputElement;
    replaceField: HTMLInputElement | null = null;
    caseField: HTMLInputElement;
    wordField: HTMLInputElement;
    reField: HTMLInputElement;
    caseBtn: HTMLButtonElement;
    wordBtn: HTMLButtonElement;
    reBtn: HTMLButtonElement;
    toggleReplaceBtn: HTMLButtonElement | null = null;
    replaceRow: HTMLElement | null = null;
    isReplaceOpen = false;
    query: SearchQuery;
    view: EditorView;

    constructor(view: EditorView) {
        this.view = view;
        const query = (this.query = getSearchQuery(view.state));

        this.commit = this.commit.bind(this);

        this.dom = document.createElement('div');
        this.dom.className = 'cm-search cm-search-vscode';
        this.dom.onkeydown = (e) => this.keydown(e);

        // Hidden checkboxes for backwards compatibility with any query selectors
        this.caseField = document.createElement('input');
        this.caseField.type = 'checkbox';
        this.caseField.name = 'case';
        this.caseField.style.display = 'none';
        this.caseField.checked = query.caseSensitive;

        this.wordField = document.createElement('input');
        this.wordField.type = 'checkbox';
        this.wordField.name = 'word';
        this.wordField.style.display = 'none';
        this.wordField.checked = query.wholeWord;

        this.reField = document.createElement('input');
        this.reField.type = 'checkbox';
        this.reField.name = 're';
        this.reField.style.display = 'none';
        this.reField.checked = query.regexp;

        this.dom.append(this.caseField, this.wordField, this.reField);

        // Row 1: Find
        const findRow = document.createElement('div');

        findRow.className = 'cm-search-row';

        if (!view.state.readOnly) {
            this.toggleReplaceBtn = document.createElement('button');
            this.toggleReplaceBtn.type = 'button';
            this.toggleReplaceBtn.className = 'cm-search-chevron';
            this.toggleReplaceBtn.title = 'Toggle Replace (Ctrl+H)';
            this.toggleReplaceBtn.setAttribute('aria-label', 'Toggle Replace');
            this.toggleReplaceBtn.style.backgroundImage = 'none';
            this.toggleReplaceBtn.style.backgroundColor = 'var(--popover)';
            this.toggleReplaceBtn.style.borderColor = 'var(--border)';
            this.toggleReplaceBtn.style.color = 'var(--foreground)';
            this.toggleReplaceBtn.onclick = () => {
                this.isReplaceOpen = !this.isReplaceOpen;
                this.updateChevron();
                if (this.isReplaceOpen) {
                    this.replaceField?.focus();
                }
            };

            findRow.appendChild(this.toggleReplaceBtn);
        }

        const searchWrap = document.createElement('div');

        searchWrap.className = 'cm-search-input-wrap';

        this.searchField = document.createElement('input');
        this.searchField.className = 'cm-textfield';
        this.searchField.name = 'search';
        this.searchField.placeholder = 'Find';
        this.searchField.setAttribute('aria-label', 'Find');
        this.searchField.setAttribute('main-field', 'true');
        this.searchField.value = query.search;
        this.searchField.onchange = this.commit;
        this.searchField.oninput = this.commit;

        const togglesWrap = document.createElement('div');

        togglesWrap.className = 'cm-search-toggles';

        this.caseBtn = this.createToggle('Aa', 'Match Case (Alt+C)', query.caseSensitive, () => {
            this.caseField.checked = !this.caseField.checked;
            this.updateToggleState(this.caseBtn, this.caseField.checked);
            this.commit();
        });

        this.wordBtn = this.createToggle('\\b', 'Match Whole Word (Alt+W)', query.wholeWord, () => {
            this.wordField.checked = !this.wordField.checked;
            this.updateToggleState(this.wordBtn, this.wordField.checked);
            this.commit();
        });

        this.reBtn = this.createToggle('.*', 'Use Regular Expression (Alt+R)', query.regexp, () => {
            this.reField.checked = !this.reField.checked;
            this.updateToggleState(this.reBtn, this.reField.checked);
            this.commit();
        });

        togglesWrap.append(this.caseBtn, this.wordBtn, this.reBtn);
        searchWrap.append(this.searchField, togglesWrap);

        const findNav = document.createElement('div');

        findNav.className = 'cm-search-nav';

        const prevBtn = this.createIconButton(
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>',
            'Previous Match (Shift+Enter)',
            'prev',
            () => findPrevious(this.view)
        );

        const nextBtn = this.createIconButton(
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
            'Next Match (Enter)',
            'next',
            () => findNext(this.view)
        );

        const allBtn = this.createActionButton(
            'All',
            'Select All Matches (Alt+Enter)',
            'select',
            () => selectMatches(this.view),
            'ghost'
        );

        const closeBtn = document.createElement('button');

        closeBtn.type = 'button';
        closeBtn.className = 'cm-button cm-icon-button cm-search-close';
        closeBtn.name = 'close';
        closeBtn.title = 'Close (Escape)';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.style.position = 'static';
        closeBtn.style.backgroundImage = 'none';
        closeBtn.style.backgroundColor = 'var(--destructive)';
        closeBtn.style.border = '1px solid var(--destructive)';
        closeBtn.style.color = 'var(--destructive-foreground, white)';
        closeBtn.innerHTML =
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
        closeBtn.onclick = () => closeSearchPanel(this.view);

        findNav.append(prevBtn, nextBtn, allBtn, closeBtn);
        findRow.append(searchWrap, findNav);
        this.dom.appendChild(findRow);

        // Row 2: Replace (if not readOnly)
        if (!view.state.readOnly) {
            this.replaceRow = document.createElement('div');
            this.replaceRow.className = 'cm-search-row cm-replace-row';

            const spacer = document.createElement('div');

            spacer.className = 'cm-search-chevron-spacer';
            this.replaceRow.appendChild(spacer);

            const replaceWrap = document.createElement('div');

            replaceWrap.className = 'cm-search-input-wrap';

            this.replaceField = document.createElement('input');
            this.replaceField.className = 'cm-textfield cm-replace-input';
            this.replaceField.name = 'replace';
            this.replaceField.placeholder = 'Replace';
            this.replaceField.setAttribute('aria-label', 'Replace');
            this.replaceField.value = query.replace;
            this.replaceField.onchange = this.commit;
            this.replaceField.oninput = this.commit;

            replaceWrap.appendChild(this.replaceField);

            const replaceNav = document.createElement('div');

            replaceNav.className = 'cm-search-nav';

            const replaceBtn = this.createActionButton(
                'Replace',
                'Replace (Enter)',
                'replace',
                () => replaceNext(this.view),
                'primary'
            );
            const replaceAllBtn = this.createActionButton(
                'All',
                'Replace All',
                'replaceAll',
                () => replaceAll(this.view),
                'secondary'
            );

            replaceNav.append(replaceBtn, replaceAllBtn);
            this.replaceRow.append(replaceWrap, replaceNav);
            this.dom.appendChild(this.replaceRow);
        }

        // Apply chevron icon and initial hidden state for replace
        this.updateChevron();
    }

    private updateChevron() {
        if (this.toggleReplaceBtn) {
            this.toggleReplaceBtn.innerHTML = this.isReplaceOpen
                ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>'
                : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
            this.toggleReplaceBtn.title = this.isReplaceOpen ? 'Hide Replace' : 'Toggle Replace (Ctrl+H)';
            this.toggleReplaceBtn.setAttribute('aria-expanded', String(this.isReplaceOpen));
        }

        if (this.replaceRow) {
            this.replaceRow.classList.toggle('is-hidden', !this.isReplaceOpen);
            this.replaceRow.style.setProperty('display', this.isReplaceOpen ? 'flex' : 'none', 'important');
        }
    }

    private createToggle(label: string, title: string, active: boolean, onClick: () => void): HTMLButtonElement {
        const btn = document.createElement('button');

        btn.type = 'button';
        btn.className = `cm-search-toggle${active ? ' is-active' : ''}`;
        btn.title = title;
        btn.setAttribute('aria-pressed', String(active));
        btn.textContent = label;
        btn.style.backgroundImage = 'none';
        btn.onclick = onClick;

        return btn;
    }

    private updateToggleState(btn: HTMLButtonElement, active: boolean) {
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-pressed', String(active));
    }

    private createIconButton(svg: string, title: string, name: string, onClick: () => void): HTMLButtonElement {
        const btn = document.createElement('button');

        btn.type = 'button';
        btn.className = 'cm-button cm-icon-button';
        btn.name = name;
        btn.title = title;
        btn.setAttribute('aria-label', title);
        btn.style.backgroundImage = 'none';
        btn.style.backgroundColor = 'var(--popover)';
        btn.style.color = 'var(--foreground)';
        btn.style.border = '1px solid var(--border)';
        btn.innerHTML = svg;
        btn.onclick = onClick;

        return btn;
    }

    private createActionButton(
        text: string,
        title: string,
        name: string,
        onClick: () => void,
        variant: 'primary' | 'secondary' | 'ghost' = 'secondary'
    ): HTMLButtonElement {
        const btn = document.createElement('button');

        btn.type = 'button';
        btn.className = `cm-button cm-action-button cm-${variant}-action`;
        btn.name = name;
        btn.title = title;
        btn.setAttribute('aria-label', title);
        btn.style.backgroundImage = 'none';
        if (variant === 'primary') {
            btn.style.backgroundColor = 'var(--primary)';
            btn.style.color = 'var(--primary-foreground, white)';
            btn.style.border = '1px solid var(--primary)';
        } else {
            btn.style.backgroundColor = 'var(--popover)';
            btn.style.color = 'var(--foreground)';
            btn.style.border = '1px solid var(--border)';
        }

        btn.textContent = text;
        btn.onclick = onClick;

        return btn;
    }

    commit() {
        const query = new SearchQuery({
            search: this.searchField.value,
            caseSensitive: this.caseField.checked,
            regexp: this.reField.checked,
            wholeWord: this.wordField.checked,
            replace: this.replaceField?.value ?? '',
        });

        if (!query.eq(this.query)) {
            this.query = query;
            this.view.dispatch({ effects: setSearchQuery.of(query) });
        }
    }

    keydown(e: KeyboardEvent) {
        const action = this.shortcut(e);

        if (action) {
            e.preventDefault();
            action();
        }
    }

    shortcut(e: KeyboardEvent): (() => void) | null {
        const key = e.key.toLowerCase();

        if (e.key === 'Escape') {
            return () => closeSearchPanel(this.view);
        }

        if (e.key === 'Enter' && e.target === this.searchField) {
            return () => (e.shiftKey ? findPrevious : findNext)(this.view);
        }

        if (e.key === 'Enter' && e.target === this.replaceField) {
            return () => replaceNext(this.view);
        }

        if ((e.ctrlKey || e.metaKey) && key === 'h' && !this.view.state.readOnly) {
            return () => this.toggleReplace();
        }

        const toggle = e.altKey ? this.optionFor(key) : null;

        return toggle ? () => this.toggleOption(...toggle) : null;
    }

    optionFor(key: string): [HTMLInputElement, HTMLButtonElement] | null {
        switch (key) {
            case 'c':
                return [this.caseField, this.caseBtn];
            case 'w':
                return [this.wordField, this.wordBtn];
            case 'r':
                return [this.reField, this.reBtn];
            default:
                return null;
        }
    }

    toggleReplace() {
        this.isReplaceOpen = !this.isReplaceOpen;
        this.updateChevron();

        if (this.isReplaceOpen) {
            this.replaceField?.focus();
        }
    }

    toggleOption(field: HTMLInputElement, button: HTMLButtonElement) {
        field.checked = !field.checked;
        this.updateToggleState(button, field.checked);
        this.commit();
    }

    update(update: ViewUpdate) {
        for (const tr of update.transactions) {
            for (const effect of tr.effects) {
                if (effect.is(setSearchQuery) && !effect.value.eq(this.query)) {
                    this.setQuery(effect.value);
                }
            }
        }
    }

    setQuery(query: SearchQuery) {
        this.query = query;
        this.searchField.value = query.search;
        if (this.replaceField) {
            this.replaceField.value = query.replace;
        }

        this.caseField.checked = query.caseSensitive;
        this.updateToggleState(this.caseBtn, query.caseSensitive);
        this.wordField.checked = query.wholeWord;
        this.updateToggleState(this.wordBtn, query.wholeWord);
        this.reField.checked = query.regexp;
        this.updateToggleState(this.reBtn, query.regexp);
    }

    mount() {
        this.searchField.select();
    }
}
