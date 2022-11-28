import { autocompletion, completionKeymap, closeBrackets, closeBracketsKeymap } from '@codemirror/autocomplete';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import {
    defaultHighlightStyle,
    syntaxHighlighting,
    indentOnInput,
    bracketMatching,
    foldGutter,
    foldKeymap,
    LanguageDescription,
    indentUnit,
} from '@codemirror/language';
import { languages } from '@codemirror/language-data';
import { lintKeymap } from '@codemirror/lint';
import { searchKeymap, highlightSelectionMatches } from '@codemirror/search';
import type { Extension } from '@codemirror/state';
import { EditorState, Compartment } from '@codemirror/state';
import {
    keymap,
    highlightSpecialChars,
    drawSelection,
    highlightActiveLine,
    dropCursor,
    rectangularSelection,
    crosshairCursor,
    lineNumbers,
    highlightActiveLineGutter,
    EditorView,
} from '@codemirror/view';
import type { CSSProperties } from 'react';
import { useEffect, useRef, useState } from 'react';

import { ayuMirageHighlightStyle, ayuMirageTheme } from './theme';

function findLanguageByFilename(filename: string): LanguageDescription | undefined {
    const language = LanguageDescription.matchFilename(languages, filename);
    if (language !== null) {
        return language;
    }

    return undefined;
}

const defaultExtensions: Extension = [
    // Ayu Mirage
    ayuMirageTheme,
    syntaxHighlighting(ayuMirageHighlightStyle),

    lineNumbers(),
    highlightActiveLineGutter(),
    highlightSpecialChars(),
    history(),
    foldGutter(),
    drawSelection(),
    dropCursor(),
    EditorState.allowMultipleSelections.of(true),
    indentOnInput(),
    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
    bracketMatching(),
    closeBrackets(),
    autocompletion(),
    rectangularSelection(),
    crosshairCursor(),
    highlightActiveLine(),
    highlightSelectionMatches(),
    keymap.of([
        ...closeBracketsKeymap,
        ...defaultKeymap,
        ...searchKeymap,
        ...historyKeymap,
        ...foldKeymap,
        ...completionKeymap,
        ...lintKeymap,
        indentWithTab,
    ]),
    EditorState.tabSize.of(4),
    indentUnit.of('\t'),
];

export interface EditorProps {
    // DOM
    className?: string;
    style?: CSSProperties;

    // CodeMirror Config
    extensions?: Extension[];
    language?: LanguageDescription;

    // Options
    filename?: string;
    initialContent?: string;

    // ?
    fetchContent?: (callback: () => Promise<string>) => void;

    // Events
    onContentSaved?: () => void;
    onLanguageChanged?: (language: LanguageDescription | undefined) => void;
}

export function Editor(props: EditorProps) {
    const ref = useRef<HTMLDivElement>(null);

    const [view, setView] = useState<EditorView>();

    // eslint-disable-next-line react/hook-use-state
    const [languageConfig] = useState(new Compartment());
    // eslint-disable-next-line react/hook-use-state
    const [keybindings] = useState(new Compartment());

    const createEditorState = () =>
        EditorState.create({
            doc: props.initialContent,
            extensions: [
                defaultExtensions,
                props.extensions === undefined ? [] : props.extensions,
                languageConfig.of([]),
                keybindings.of([]),
            ],
        });

    useEffect(() => {
        if (ref.current === null) {
            return;
        }

        setView(
            new EditorView({
                state: createEditorState(),
                parent: ref.current,
            }),
        );

        return () => {
            if (view === undefined) {
                return;
            }

            view.destroy();
            setView(undefined);
        };
    }, [ref]);

    useEffect(() => {
        if (view === undefined) {
            return;
        }

        view.setState(createEditorState());
    }, [props.initialContent]);

    useEffect(() => {
        if (view === undefined) {
            return;
        }

        const language = props.language ?? findLanguageByFilename(props.filename ?? '');
        if (language === undefined) {
            return;
        }

        void language.load().then(language => {
            view.dispatch({
                effects: languageConfig.reconfigure(language),
            });
        });

        if (props.onLanguageChanged !== undefined) {
            props.onLanguageChanged(language);
        }
    }, [view, props.filename, props.language]);

    useEffect(() => {
        if (props.fetchContent === undefined) {
            return;
        }

        if (!view) {
            props.fetchContent(async () => {
                throw new Error('no editor session has been configured');
            });
            return;
        }

        const { onContentSaved } = props;
        if (onContentSaved !== undefined) {
            view.dispatch({
                effects: keybindings.reconfigure(
                    keymap.of([
                        {
                            key: 'Mod-s',
                            run() {
                                onContentSaved();
                                return true;
                            },
                        },
                    ]),
                ),
            });
        }

        props.fetchContent(async () => view.state.doc.toJSON().join('\n'));
    }, [view, props.fetchContent, props.onContentSaved]);

    return <div ref={ref} className={`relative ${props.className ?? ''}`.trimEnd()} style={props.style} />;
}
