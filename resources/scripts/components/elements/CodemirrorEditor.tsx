import React, { useEffect, useImperativeHandle, useMemo, useRef, useState } from 'react';
import { basicSetup } from 'codemirror';
import { Compartment, EditorState, Prec } from '@codemirror/state';
import { HighlightStyle, indentUnit, syntaxHighlighting } from '@codemirror/language';
import { EditorView, keymap, type Panel, type ViewUpdate } from '@codemirror/view';
import {
    search,
    SearchQuery,
    closeSearchPanel,
    findNext,
    findPrevious,
    getSearchQuery,
    replaceAll,
    replaceNext,
    selectMatches,
    setSearchQuery,
} from '@codemirror/search';
import { tags } from '@lezer/highlight';
import { resolveCodemirrorLanguage } from '@/components/elements/codemirror/languages';
import { editorContainerClass } from '@/components/elements/codemirror/layout';
import { cn } from '@/lib/cn';

export interface Props {
    ref?: React.Ref<CodemirrorEditorHandle>;
    className?: string;
    /** The document the editor opens with; later changes are ignored until the editor remounts. */
    initialContent?: string;
    mode: string;
    onContentSaved: () => void;
    onContentChanged?: (content: string) => void;
}

export interface CodemirrorEditorHandle {
    getValue: () => string;
}

const panelEditorTheme = EditorView.theme(
    {
        '&': {
            backgroundColor: 'var(--editor-background)',
            color: 'var(--editor-foreground)',
            fontSize: 'var(--text-xs)',
            height: '100%',
        },
        // The scroller and the sticky gutters are positioned, so they paint over an outline
        // on the editor itself: the active line and the line numbers hid the focus ring.
        '&.cm-focused': {
            outline: 'none',
        },
        '&.cm-focused::after': {
            content: '""',
            position: 'absolute',
            inset: '0',
            border: '2px solid var(--ring)',
            borderRadius: 'inherit',
            pointerEvents: 'none',
            zIndex: '201',
        },
        '.cm-scroller': {
            fontFamily: 'var(--font-mono)',
            lineHeight: 'var(--text-sm--line-height)',
            overflow: 'auto',
        },
        '.cm-content': {
            caretColor: 'var(--editor-caret)',
            minHeight: '100%',
            padding: 'calc(var(--spacing) * 3) 0 50vh',
        },
        '.cm-line': {
            padding: '0 calc(var(--spacing) * 3)',
        },
        '.cm-selectionBackground, &.cm-focused .cm-selectionBackground': {
            backgroundColor: 'var(--editor-selection)',
        },
        '.cm-cursor': {
            borderLeftColor: 'var(--editor-caret)',
        },
        '.cm-gutters': {
            backgroundColor: 'var(--editor-background)',
            borderRight: '1px solid var(--editor-border)',
            color: 'var(--editor-muted)',
        },
        '.cm-lineNumbers .cm-gutterElement': {
            padding: '0 calc(var(--spacing) * 3)',
        },
        '.cm-foldGutter .cm-gutterElement': {
            color: 'var(--editor-muted)',
            padding: '0 calc(var(--spacing) * 1.5)',
        },
        '.cm-foldPlaceholder': {
            backgroundColor: 'var(--editor-border)',
            border: 'none',
            color: 'var(--editor-foreground)',
            margin: '0 var(--spacing)',
        },
        '.cm-activeLine, .cm-activeLineGutter': {
            backgroundColor: 'var(--editor-active-line)',
        },
        '.cm-matchingBracket, .cm-nonmatchingBracket': {
            backgroundColor: 'var(--editor-selection)',
            color: 'var(--editor-caret)',
        },
        '.cm-searchMatch': {
            backgroundColor: 'var(--editor-search-match)',
        },
        '.cm-searchMatch.cm-searchMatch-selected': {
            backgroundColor: 'var(--editor-search-match-selected)',
        },
        '.cm-tooltip, .cm-tooltip.cm-tooltip-autocomplete': {
            backgroundColor: 'var(--editor-tooltip)',
            border: '1px solid var(--editor-tooltip-border)',
            color: 'var(--editor-foreground)',
        },
        '.cm-tooltip-autocomplete ul li[aria-selected]': {
            backgroundColor: 'var(--editor-selection)',
            color: 'var(--editor-selected-foreground)',
        },
        '.cm-panels': {
            backgroundColor: 'transparent !important',
            border: 'none !important',
            boxShadow: 'none !important',
            zIndex: '50',
            pointerEvents: 'none !important',
        },
        '.cm-panels.cm-panels-top': {
            position: 'absolute !important',
            top: '0 !important',
            left: '0 !important',
            right: '0 !important',
            height: '0 !important',
            overflow: 'visible !important',
            pointerEvents: 'none !important',
            border: 'none !important',
            backgroundColor: 'transparent !important',
            boxShadow: 'none !important',
            zIndex: '100 !important',
        },
        '.cm-panels.cm-panels-bottom': {
            display: 'none !important',
        },
        '.cm-search-vscode': {
            position: 'absolute !important',
            top: '0.625rem !important',
            right: '1rem !important',
            pointerEvents: 'auto !important',
            width: '24rem !important',
            maxWidth: 'calc(100% - 2rem) !important',
            backgroundColor: 'var(--card) !important',
            border: '1px solid var(--border) !important',
            borderRadius: 'var(--radius, 0.5rem) !important',
            boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.6) !important',
            padding: '0.5rem 0.625rem !important',
            display: 'flex !important',
            flexDirection: 'column !important',
            gap: '0.375rem !important',
            boxSizing: 'border-box !important',
            fontFamily: 'var(--font-sans) !important',
            zIndex: '100 !important',
        },
        '.cm-search-row': {
            display: 'flex',
            alignItems: 'center',
            gap: '0.25rem',
            width: '100%',
        },
        '.cm-search-vscode .cm-replace-row.is-hidden': {
            display: 'none !important',
        },
        '.cm-search-input-wrap': {
            position: 'relative !important',
            flex: '1 1 auto !important',
            minWidth: '0 !important',
            display: 'flex !important',
            alignItems: 'center !important',
        },
        '.cm-search-vscode .cm-textfield': {
            backgroundColor: 'var(--input) !important',
            border: '1px solid var(--border) !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            color: 'var(--foreground) !important',
            fontFamily: 'var(--font-sans) !important',
            fontSize: '0.75rem !important',
            height: '1.875rem !important',
            lineHeight: '1.875rem !important',
            padding: '0 4.875rem 0 0.5rem !important',
            width: '100% !important',
            outline: 'none !important',
            margin: '0 !important',
            boxSizing: 'border-box !important',
            transition: 'border-color 0.12s ease, box-shadow 0.12s ease !important',
        },
        '.cm-search-vscode .cm-textfield.cm-replace-input': {
            padding: '0 0.5rem !important',
        },
        '.cm-search-vscode .cm-textfield::placeholder': {
            color: 'var(--muted-foreground) !important',
            opacity: '0.6 !important',
        },
        '.cm-search-vscode .cm-textfield:hover': {
            borderColor: 'var(--ring) !important',
        },
        '.cm-search-vscode .cm-textfield:focus': {
            borderColor: 'var(--primary) !important',
            boxShadow: '0 0 0 2px color-mix(in oklch, var(--ring) 50%, transparent) !important',
        },
        '.cm-search-toggles': {
            position: 'absolute !important',
            right: '3px !important',
            top: '3px !important',
            bottom: '3px !important',
            display: 'flex !important',
            alignItems: 'center !important',
            gap: '2px !important',
        },
        '.cm-button, .cm-button:active': {
            backgroundImage: 'none !important',
        },
        '.cm-search-vscode .cm-button, .cm-search-vscode .cm-search-close, .cm-search-vscode .cm-search-chevron, .cm-search-vscode .cm-search-toggle': {
            appearance: 'none !important',
            WebkitAppearance: 'none !important',
            outline: 'none !important',
            border: 'none !important',
            backgroundImage: 'none !important',
            backgroundColor: 'transparent !important',
            color: 'var(--muted-foreground) !important',
            cursor: 'pointer !important',
            padding: '0 !important',
            margin: '0 !important',
            boxSizing: 'border-box !important',
            fontFamily: 'var(--font-sans) !important',
            display: 'inline-flex !important',
            alignItems: 'center !important',
            justifyContent: 'center !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            transition: 'background-color 0.12s ease, color 0.12s ease, border-color 0.12s ease, opacity 0.12s ease !important',
        },
        '.cm-search-vscode .cm-search-toggle': {
            width: '1.375rem !important',
            height: '1.375rem !important',
            borderRadius: '0.2rem !important',
            fontFamily: 'var(--font-sans) !important',
            fontSize: '0.6875rem !important',
            fontWeight: '600 !important',
            lineHeight: '1 !important',
            backgroundImage: 'none !important',
            backgroundColor: 'transparent !important',
            border: '1px solid transparent !important',
            color: 'var(--muted-foreground) !important',
        },
        '.cm-search-vscode .cm-search-toggle:hover': {
            backgroundColor: 'var(--secondary) !important',
            borderColor: 'var(--border) !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-search-toggle.is-active, .cm-search-vscode .cm-search-toggle[aria-pressed="true"]': {
            backgroundColor: 'var(--primary) !important',
            borderColor: 'var(--primary) !important',
            color: 'var(--primary-foreground) !important',
        },
        '.cm-search-nav': {
            display: 'inline-flex !important',
            alignItems: 'center !important',
            gap: '3px !important',
            flexShrink: '0 !important',
        },
        '.cm-search-vscode .cm-icon-button': {
            width: '1.75rem !important',
            height: '1.75rem !important',
            backgroundImage: 'none !important',
            backgroundColor: 'var(--popover) !important',
            border: '1px solid var(--border) !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-icon-button:hover': {
            backgroundImage: 'none !important',
            backgroundColor: 'var(--secondary) !important',
            borderColor: 'var(--secondary) !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-search-close, .cm-search-vscode button[name="close"]': {
            position: 'static !important',
            top: 'auto !important',
            right: 'auto !important',
            width: '1.75rem !important',
            height: '1.75rem !important',
            margin: '0 !important',
            padding: '0 !important',
            backgroundImage: 'none !important',
            backgroundColor: 'var(--destructive) !important',
            border: '1px solid var(--destructive) !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            color: 'var(--destructive-foreground, white) !important',
            display: 'inline-flex !important',
            alignItems: 'center !important',
            justifyContent: 'center !important',
            cursor: 'pointer !important',
            transition: 'opacity 0.12s ease, filter 0.12s ease !important',
        },
        '.cm-search-vscode .cm-search-close:hover, .cm-search-vscode button[name="close"]:hover': {
            backgroundImage: 'none !important',
            backgroundColor: 'var(--destructive) !important',
            opacity: '0.85 !important',
            filter: 'brightness(1.1) !important',
        },
        '.cm-search-vscode .cm-search-chevron': {
            width: '1.375rem !important',
            height: '1.875rem !important',
            flexShrink: '0 !important',
            backgroundImage: 'none !important',
            backgroundColor: 'var(--popover) !important',
            border: '1px solid var(--border) !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-search-chevron:hover': {
            color: 'var(--foreground) !important',
            backgroundColor: 'var(--secondary) !important',
            borderColor: 'var(--secondary) !important',
        },
        '.cm-search-vscode .cm-search-chevron-spacer': {
            width: '1.375rem !important',
            height: '1.875rem !important',
            flexShrink: '0 !important',
        },
        '.cm-search-vscode .cm-action-button': {
            height: '1.875rem !important',
            padding: '0 0.625rem !important',
            fontSize: '0.6875rem !important',
            fontWeight: '600 !important',
            textTransform: 'uppercase !important',
            letterSpacing: '0.025em !important',
            lineHeight: '1 !important',
            boxSizing: 'border-box !important',
            backgroundImage: 'none !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
        },
        '.cm-search-vscode .cm-ghost-action': {
            backgroundColor: 'var(--popover) !important',
            border: '1px solid var(--border) !important',
            backgroundImage: 'none !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-ghost-action:hover': {
            backgroundColor: 'var(--secondary) !important',
            borderColor: 'var(--secondary) !important',
            backgroundImage: 'none !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-primary-action': {
            backgroundColor: 'var(--primary) !important',
            border: '1px solid var(--primary) !important',
            backgroundImage: 'none !important',
            color: 'var(--primary-foreground, white) !important',
        },
        '.cm-search-vscode .cm-primary-action:hover': {
            backgroundImage: 'none !important',
            filter: 'brightness(1.1) !important',
            opacity: '0.9 !important',
        },
        '.cm-search-vscode .cm-secondary-action': {
            backgroundColor: 'var(--popover) !important',
            border: '1px solid var(--border) !important',
            backgroundImage: 'none !important',
            color: 'var(--foreground) !important',
        },
        '.cm-search-vscode .cm-secondary-action:hover': {
            backgroundColor: 'var(--secondary) !important',
            borderColor: 'var(--secondary) !important',
            backgroundImage: 'none !important',
            color: 'var(--foreground) !important',
        },
        '.cm-dialog': {
            backgroundColor: 'var(--card) !important',
            padding: 'calc(var(--spacing) * 2) !important',
            display: 'flex !important',
            alignItems: 'center !important',
            gap: 'calc(var(--spacing) * 2) !important',
            borderRadius: 'var(--radius-md, 0.375rem) !important',
            border: '1px solid var(--border) !important',
            boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.5) !important',
            position: 'relative !important',
        },
        '.cm-dialog .cm-textfield': {
            backgroundColor: 'var(--input) !important',
            border: '1px solid var(--border) !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            color: 'var(--foreground) !important',
            fontFamily: 'var(--font-mono) !important',
            fontSize: 'var(--text-xs) !important',
            height: '1.75rem !important',
            lineHeight: '1.25rem !important',
            padding: '0 calc(var(--spacing) * 2) !important',
            outline: 'none !important',
            margin: '0 !important',
            boxSizing: 'border-box !important',
            transition: 'border-color 0.15s ease, box-shadow 0.15s ease !important',
        },
        '.cm-dialog .cm-textfield:focus': {
            borderColor: 'var(--primary) !important',
            boxShadow: '0 0 0 2px color-mix(in oklch, var(--ring) 50%, transparent) !important',
        },
        '.cm-dialog .cm-dialog-close': {
            marginLeft: 'auto !important',
            width: '1.5rem !important',
            height: '1.5rem !important',
            borderRadius: 'var(--radius-sm, 0.25rem) !important',
            border: 'none !important',
            backgroundColor: 'transparent !important',
            color: 'var(--muted-foreground) !important',
            fontSize: '1rem !important',
            lineHeight: '1 !important',
            padding: '0 !important',
            margin: '0 !important',
            cursor: 'pointer !important',
            display: 'inline-flex !important',
            alignItems: 'center !important',
            justifyContent: 'center !important',
            transition: 'background-color 0.15s ease, color 0.15s ease !important',
            boxSizing: 'border-box !important',
        },
        '.cm-dialog .cm-dialog-close:hover': {
            backgroundColor: 'var(--secondary) !important',
            color: 'var(--foreground) !important',
        },
    },
    { dark: true }
);

const panelHighlightStyle = HighlightStyle.define([
    { tag: tags.keyword, color: 'var(--editor-syntax-keyword)' },
    {
        tag: [tags.name, tags.deleted, tags.character, tags.macroName],
        color: 'var(--editor-syntax-name)',
    },
    {
        tag: [tags.propertyName, tags.variableName],
        color: 'var(--editor-foreground)',
    },
    {
        tag: [tags.function(tags.variableName), tags.labelName],
        color: 'var(--editor-syntax-function)',
    },
    {
        tag: [tags.color, tags.constant(tags.name), tags.standard(tags.name)],
        color: 'var(--editor-syntax-constant)',
    },
    {
        tag: [tags.definition(tags.name), tags.separator],
        color: 'var(--editor-syntax-definition)',
    },
    {
        tag: [tags.typeName, tags.className, tags.number, tags.changed, tags.annotation, tags.modifier],
        color: 'var(--editor-syntax-type)',
    },
    {
        tag: [tags.operator, tags.operatorKeyword, tags.url, tags.escape, tags.regexp],
        color: 'var(--editor-syntax-operator)',
    },
    { tag: [tags.meta, tags.comment], color: 'var(--editor-muted)' },
    { tag: tags.strong, fontWeight: 'var(--font-weight-bold)' },
    { tag: tags.emphasis, fontStyle: 'italic' },
    { tag: tags.strikethrough, textDecoration: 'line-through' },
    {
        tag: tags.link,
        color: 'var(--editor-syntax-definition)',
        textDecoration: 'underline',
    },
    {
        tag: [tags.string, tags.special(tags.brace)],
        color: 'var(--editor-syntax-string)',
    },
    { tag: tags.invalid, color: 'var(--editor-syntax-invalid)' },
]);

class CustomSearchPanel implements Panel {
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
        this.searchField.onkeyup = this.commit;

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
            this.replaceField.onkeyup = this.commit;

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

    private createToggle(
        label: string,
        title: string,
        active: boolean,
        onClick: () => void
    ): HTMLButtonElement {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cm-search-toggle' + (active ? ' is-active' : '');
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

    private createIconButton(
        svg: string,
        title: string,
        name: string,
        onClick: () => void
    ): HTMLButtonElement {
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
        if (e.key === 'Escape') {
            e.preventDefault();
            closeSearchPanel(this.view);
        } else if (e.key === 'Enter' && e.target === this.searchField) {
            e.preventDefault();
            (e.shiftKey ? findPrevious : findNext)(this.view);
        } else if (e.key === 'Enter' && e.target === this.replaceField) {
            e.preventDefault();
            replaceNext(this.view);
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'h' && !this.view.state.readOnly) {
            e.preventDefault();
            this.isReplaceOpen = !this.isReplaceOpen;
            this.updateChevron();
            if (this.isReplaceOpen) {
                this.replaceField?.focus();
            }
        } else if (e.altKey && e.key.toLowerCase() === 'c') {
            e.preventDefault();
            this.caseField.checked = !this.caseField.checked;
            this.updateToggleState(this.caseBtn, this.caseField.checked);
            this.commit();
        } else if (e.altKey && e.key.toLowerCase() === 'w') {
            e.preventDefault();
            this.wordField.checked = !this.wordField.checked;
            this.updateToggleState(this.wordBtn, this.wordField.checked);
            this.commit();
        } else if (e.altKey && e.key.toLowerCase() === 'r') {
            e.preventDefault();
            this.reField.checked = !this.reField.checked;
            this.updateToggleState(this.reBtn, this.reField.checked);
            this.commit();
        }
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

const createEditorState = (
    doc: string,
    mode: string,
    languageCompartment: Compartment,
    onContentSaved: { current: () => void },
    onContentChanged: { current: Props['onContentChanged'] }
) =>
    EditorState.create({
        doc,
        extensions: [
            basicSetup,
            search({ createPanel: (view) => new CustomSearchPanel(view), top: true }),
            panelEditorTheme,
            syntaxHighlighting(panelHighlightStyle),
            EditorState.tabSize.of(4),
            indentUnit.of('    '),
            EditorView.lineWrapping,
            EditorView.contentAttributes.of({
                'aria-label': 'File editor',
                autocapitalize: 'off',
                autocorrect: 'off',
            }),
            Prec.high(
                keymap.of([
                    {
                        key: 'Mod-s',
                        preventDefault: true,
                        run: () => {
                            onContentSaved.current();

                            return true;
                        },
                    },
                ])
            ),
            languageCompartment.of(resolveCodemirrorLanguage(mode)),
            EditorView.updateListener.of((update) => {
                if (update.docChanged) {
                    onContentChanged.current?.(update.state.doc.toString());
                }
            }),
        ],
    });

export default function CodemirrorEditor({
    ref,
    className,
    initialContent,
    mode,
    onContentSaved,
    onContentChanged,
}: Props) {
    const editor = useRef<EditorView | null>(null);
    const mount = useRef<HTMLDivElement | null>(null);
    const onContentSavedRef = useRef(onContentSaved);
    const onContentChangedRef = useRef(onContentChanged);
    const modeRef = useRef(mode);
    const [doc] = useState(initialContent ?? '');
    const languageCompartment = useMemo(() => new Compartment(), []);

    useEffect(() => {
        onContentSavedRef.current = onContentSaved;
        onContentChangedRef.current = onContentChanged;
        modeRef.current = mode;
    });

    useImperativeHandle(
        ref,
        () => ({
            getValue: () => editor.current?.state.doc.toString() ?? '',
        }),
        []
    );

    useEffect(() => {
        if (!mount.current) {
            return;
        }

        const view = new EditorView({
            parent: mount.current,
            state: createEditorState(doc, modeRef.current, languageCompartment, onContentSavedRef, onContentChangedRef),
        });

        editor.current = view;

        return () => {
            view.destroy();
            if (editor.current === view) {
                editor.current = null;
            }
        };
    }, [doc, languageCompartment]);

    useEffect(() => {
        editor.current?.dispatch({
            effects: languageCompartment.reconfigure(resolveCodemirrorLanguage(mode)),
        });
    }, [languageCompartment, mode]);

    return <div ref={mount} className={cn(editorContainerClass, className)} />;
}
