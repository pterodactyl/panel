import React, { useEffect, useImperativeHandle, useMemo, useRef, useState } from 'react';
import { basicSetup } from 'codemirror';
import { Compartment, EditorState, Prec } from '@codemirror/state';
import { HighlightStyle, indentUnit, syntaxHighlighting } from '@codemirror/language';
import { EditorView, keymap } from '@codemirror/view';
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
