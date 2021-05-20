import React, { useCallback, useState } from 'react';
import styled from 'styled-components/macro';
import tw, { TwStyle } from 'twin.macro';
import { autocompletion, completionKeymap } from '@codemirror/autocomplete';
import { closeBrackets, closeBracketsKeymap } from '@codemirror/closebrackets';
import { defaultKeymap, defaultTabBinding } from '@codemirror/commands';
import { commentKeymap } from '@codemirror/comment';
import { foldGutter, foldKeymap } from '@codemirror/fold';
import { lineNumbers, highlightActiveLineGutter } from '@codemirror/gutter';
import { defaultHighlightStyle } from '@codemirror/highlight';
import { history, historyKeymap } from '@codemirror/history';
import { indentOnInput, LezerLanguage } from '@codemirror/language';
import { lintKeymap } from '@codemirror/lint';
import { bracketMatching } from '@codemirror/matchbrackets';
import { rectangularSelection } from '@codemirror/rectangular-selection';
import { searchKeymap, highlightSelectionMatches } from '@codemirror/search';
import { Extension, EditorState } from '@codemirror/state';
import { StreamLanguage, StreamParser } from '@codemirror/stream-parser';
import { keymap, highlightSpecialChars, drawSelection, highlightActiveLine, EditorView } from '@codemirror/view';

import { ayuMirage } from '@/components/elements/EditorTheme';

const extensions: Extension = [
    ayuMirage,

    lineNumbers(),
    highlightActiveLineGutter(),
    highlightSpecialChars(),
    history(),
    foldGutter(),
    drawSelection(),
    EditorState.allowMultipleSelections.of(true),
    indentOnInput(),
    defaultHighlightStyle.fallback,
    bracketMatching(),
    closeBrackets(),
    autocompletion(),
    rectangularSelection(),
    highlightActiveLine(),
    highlightSelectionMatches(),
    keymap.of([
        ...closeBracketsKeymap,
        ...defaultKeymap,
        ...searchKeymap,
        ...historyKeymap,
        ...foldKeymap,
        ...commentKeymap,
        ...completionKeymap,
        ...lintKeymap,
        defaultTabBinding,
    ]),

    EditorState.tabSize.of(4),
];

const EditorContainer = styled.div<{ overrides?: TwStyle }>`
    min-height: 12rem;
    ${tw`relative`};

    & > div {
        ${props => props.overrides};

        &.cm-focused {
            outline: none;
        }
    }
`;

export interface Props {
    className?: string;
    overrides?: TwStyle;
    mode: LezerLanguage | StreamParser<any>;
    initialContent?: string;
}

export default ({ className, overrides, mode, initialContent }: Props) => {
    const [ state ] = useState<EditorState>(EditorState.create({
        doc: initialContent,
        extensions: [ ...extensions, (mode instanceof LezerLanguage) ? mode : StreamLanguage.define(mode) ],
    }));
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const [ view, setView ] = useState<EditorView>();

    const ref = useCallback((node) => {
        if (!node) {
            return;
        }

        const view = new EditorView({
            state: state,
            parent: node,
        });
        setView(view);
    }, []);

    return (
        <EditorContainer className={className} overrides={overrides} ref={ref}/>
    );
};
