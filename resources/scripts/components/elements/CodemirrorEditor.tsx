import tw from 'twin.macro';
import modes from '@/modes';
import CodeMirror from 'codemirror';
import styled from 'styled-components/macro';
import React, { useCallback, useEffect, useState } from 'react';

require('codemirror/addon/mode/simple');
require('codemirror/lib/codemirror.css');
require('codemirror/addon/edit/closetag');
require('codemirror/addon/fold/foldcode');
require('codemirror/addon/fold/xml-fold');
require('codemirror/addon/hint/css-hint');
require('codemirror/addon/hint/sql-hint');
require('codemirror/addon/hint/xml-hint');
require('codemirror/addon/dialog/dialog');
require('codemirror/addon/search/search');
require('codemirror/theme/ayu-mirage.css');
require('codemirror/addon/edit/matchtags');
require('codemirror/addon/hint/html-hint');
require('codemirror/addon/hint/show-hint');
require('codemirror/addon/fold/foldgutter');
require('codemirror/addon/fold/brace-fold');
require('codemirror/addon/fold/indent-fold');
require('codemirror/addon/fold/comment-fold');
require('codemirror/addon/dialog/dialog.css');
require('codemirror/addon/edit/closebrackets');
require('codemirror/addon/edit/matchbrackets');
require('codemirror/addon/edit/trailingspace');
require('codemirror/addon/fold/markdown-fold');
require('codemirror/addon/hint/show-hint.css');
require('codemirror/addon/fold/foldgutter.css');
require('codemirror/addon/search/jump-to-line');
require('codemirror/addon/search/searchcursor');
require('codemirror/addon/hint/javascript-hint');
require('codemirror/addon/scroll/scrollpastend');
require('codemirror/addon/scroll/simplescrollbars');
require('codemirror/addon/scroll/annotatescrollbar');
require('codemirror/addon/search/match-highlighter');
require('codemirror/addon/search/matchesonscrollbar');
require('codemirror/addon/scroll/simplescrollbars.css');
require('codemirror/addon/search/matchesonscrollbar.css');

require('codemirror/mode/go/go');
require('codemirror/mode/css/css');
require('codemirror/mode/gfm/gfm');
require('codemirror/mode/jsx/jsx');
require('codemirror/mode/lua/lua');
require('codemirror/mode/php/php');
require('codemirror/mode/pug/pug');
require('codemirror/mode/rpm/rpm');
require('codemirror/mode/sql/sql');
require('codemirror/mode/vue/vue');
require('codemirror/mode/xml/xml');
require('codemirror/mode/dart/dart');
require('codemirror/mode/diff/diff');
require('codemirror/mode/http/http');
require('codemirror/mode/perl/perl');
require('codemirror/mode/ruby/ruby');
require('codemirror/mode/rust/rust');
require('codemirror/mode/sass/sass');
require('codemirror/mode/toml/toml');
require('codemirror/mode/twig/twig');
require('codemirror/mode/yaml/yaml');
require('codemirror/mode/clike/clike');
require('codemirror/mode/julia/julia');
require('codemirror/mode/nginx/nginx');
require('codemirror/mode/shell/shell');
require('codemirror/mode/swift/swift');
require('codemirror/mode/erlang/erlang');
require('codemirror/mode/python/python');
require('codemirror/mode/smarty/smarty');
require('codemirror/mode/markdown/markdown');
require('codemirror/mode/protobuf/protobuf');
require('codemirror/mode/brainfuck/brainfuck');
require('codemirror/mode/htmlmixed/htmlmixed');
require('codemirror/mode/dockerfile/dockerfile');
require('codemirror/mode/handlebars/handlebars');
require('codemirror/mode/javascript/javascript');
require('codemirror/mode/properties/properties');
require('codemirror/mode/htmlembedded/htmlembedded');

const EditorContainer = styled.div`
    min-height: 16rem;
    height: calc(100vh - 20rem);
    ${tw`relative`};

    > div {
        ${tw`rounded h-full`};
    }

    .CodeMirror {
        font-size: 12px;
        line-height: 1.375rem;
    }

    .CodeMirror-linenumber {
        padding: 1px 12px 0 12px !important;
    }

    .CodeMirror-foldmarker {
        color: #cbccc6;
        text-shadow: none;
        margin-left: 0.25rem;
        margin-right: 0.25rem;
    }
`;

export interface Props {
    style?: React.CSSProperties;
    initialContent?: string;
    mode: string;
    filename?: string;
    onModeChanged: (mode: string) => void;
    fetchContent: (callback: () => Promise<string>) => void;
    onContentSaved: () => void;
}

const findModeByFilename = (filename: string) => {
    for (let i = 0; i < modes.length; i++) {
        const info = modes[i];

        if (info.file && info.file.test(filename)) {
            return info;
        }
    }

    const dot = filename.lastIndexOf('.');
    const ext = dot > -1 && filename.substring(dot + 1, filename.length);

    if (ext) {
        for (let i = 0; i < modes.length; i++) {
            const info = modes[i];
            if (info.ext) {
                for (let j = 0; j < info.ext.length; j++) {
                    if (info.ext[j] === ext) {
                        return info;
                    }
                }
            }
        }
    }

    return undefined;
};

export default ({ style, initialContent, filename, mode, fetchContent, onContentSaved, onModeChanged }: Props) => {
    const [editor, setEditor] = useState<CodeMirror.Editor>();

    const ref = useCallback((node) => {
        if (!node) return;

        const e = CodeMirror.fromTextArea(node, {
            mode: 'text/plain',
            theme: 'ayu-mirage',
            indentUnit: 4,
            smartIndent: true,
            tabSize: 4,
            indentWithTabs: false,
            lineWrapping: true,
            lineNumbers: true,
            foldGutter: true,
            fixedGutter: true,
            scrollbarStyle: 'overlay',
            coverGutterNextToScrollbar: false,
            readOnly: false,
            showCursorWhenSelecting: false,
            autofocus: false,
            spellcheck: true,
            autocorrect: false,
            autocapitalize: false,
            lint: false,
            // @ts-expect-error this property is actually used, the d.ts file for CodeMirror is incorrect.
            autoCloseBrackets: true,
            matchBrackets: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        });

        setEditor(e);
    }, []);

    useEffect(() => {
        if (filename === undefined) {
            return;
        }

        onModeChanged(findModeByFilename(filename)?.mime || 'text/plain');
    }, [filename]);

    useEffect(() => {
        editor && editor.setOption('mode', mode);
    }, [editor, mode]);

    useEffect(() => {
        editor && editor.setValue(initialContent || '');
    }, [editor, initialContent]);

    useEffect(() => {
        if (!editor) {
            fetchContent(() => Promise.reject(new Error('no editor session has been configured')));
            return;
        }

        editor.addKeyMap({
            'Ctrl-S': () => onContentSaved(),
            'Cmd-S': () => onContentSaved(),
        });

        fetchContent(() => Promise.resolve(editor.getValue()));
    }, [editor, fetchContent, onContentSaved]);

    return (
        <EditorContainer style={style}>
            <textarea ref={ref} />
        </EditorContainer>
    );
};
