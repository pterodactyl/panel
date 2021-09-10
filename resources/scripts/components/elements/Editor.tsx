import { autocompletion, completionKeymap } from '@codemirror/autocomplete';
import { closeBrackets, closeBracketsKeymap } from '@codemirror/closebrackets';
import { defaultKeymap, indentWithTab } from '@codemirror/commands';
import { commentKeymap } from '@codemirror/comment';
import { foldGutter, foldKeymap } from '@codemirror/fold';
import { lineNumbers, highlightActiveLineGutter } from '@codemirror/gutter';
import { defaultHighlightStyle } from '@codemirror/highlight';
import { history, historyKeymap } from '@codemirror/history';
import { indentOnInput, LanguageSupport, LRLanguage } from '@codemirror/language';
import { lintKeymap } from '@codemirror/lint';
import { bracketMatching } from '@codemirror/matchbrackets';
import { rectangularSelection } from '@codemirror/rectangular-selection';
import { searchKeymap, highlightSelectionMatches } from '@codemirror/search';
import { Compartment, Extension, EditorState } from '@codemirror/state';
import { StreamLanguage, StreamParser } from '@codemirror/stream-parser';
import { keymap, highlightSpecialChars, drawSelection, highlightActiveLine, EditorView } from '@codemirror/view';
import { clike } from '@codemirror/legacy-modes/mode/clike';
import { cppLanguage } from '@codemirror/lang-cpp';
import { cssLanguage } from '@codemirror/lang-css';
import { Cassandra, MariaSQL, MSSQL, MySQL, PostgreSQL, sql, SQLite, StandardSQL } from '@codemirror/lang-sql';
import { diff } from '@codemirror/legacy-modes/mode/diff';
import { dockerFile } from '@codemirror/legacy-modes/mode/dockerfile';
import { markdown, markdownLanguage } from '@codemirror/lang-markdown';
import { go } from '@codemirror/legacy-modes/mode/go';
import { htmlLanguage } from '@codemirror/lang-html';
import { http } from '@codemirror/legacy-modes/mode/http';
import { javascriptLanguage, typescriptLanguage } from '@codemirror/lang-javascript';
import { jsonLanguage } from '@codemirror/lang-json';
import { lua } from '@codemirror/legacy-modes/mode/lua';
import { properties } from '@codemirror/legacy-modes/mode/properties';
import { python } from '@codemirror/legacy-modes/mode/python';
import { ruby } from '@codemirror/legacy-modes/mode/ruby';
import { rustLanguage } from '@codemirror/lang-rust';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { toml } from '@codemirror/legacy-modes/mode/toml';
import { xmlLanguage } from '@codemirror/lang-xml';
import { yaml } from '@codemirror/legacy-modes/mode/yaml';
import React, { useCallback, useEffect, useState } from 'react';
import tw, { styled, TwStyle } from 'twin.macro';
import { ayuMirage } from '@/components/elements/EditorTheme';

type EditorMode = LanguageSupport | LRLanguage | StreamParser<any>;

export interface Mode {
    name: string,
    mime: string,
    mimes?: string[],
    mode?: EditorMode,
    ext?: string[],
    alias?: string[],
    file?: RegExp,
}

export const modes: Mode[] = [
    { name: 'C', mime: 'text/x-csrc', mode: clike({}), ext: [ 'c', 'h', 'ino' ] },
    { name: 'C++', mime: 'text/x-c++src', mode: cppLanguage, ext: [ 'cpp', 'c++', 'cc', 'cxx', 'hpp', 'h++', 'hh', 'hxx' ], alias: [ 'cpp' ] },
    { name: 'C#', mime: 'text/x-csharp', mode: clike({}), ext: [ 'cs' ], alias: [ 'csharp', 'cs' ] },
    { name: 'CSS', mime: 'text/css', mode: cssLanguage, ext: [ 'css' ] },
    { name: 'CQL', mime: 'text/x-cassandra', mode: sql({ dialect: Cassandra }), ext: [ 'cql' ] },
    { name: 'Diff', mime: 'text/x-diff', mode: diff, ext: [ 'diff', 'patch' ] },
    { name: 'Dockerfile', mime: 'text/x-dockerfile', mode: dockerFile, file: /^Dockerfile$/ },
    { name: 'Git Markdown', mime: 'text/x-gfm', mode: markdown({ defaultCodeLanguage: markdownLanguage }), file: /^(readme|contributing|history|license).md$/i },
    { name: 'Golang', mime: 'text/x-go', mode: go, ext: [ 'go' ] },
    { name: 'HTML', mime: 'text/html', mode: htmlLanguage, ext: [ 'html', 'htm', 'handlebars', 'hbs' ], alias: [ 'xhtml' ] },
    { name: 'HTTP', mime: 'message/http', mode: http },
    { name: 'JavaScript', mime: 'text/javascript', mimes: [ 'text/javascript', 'text/ecmascript', 'application/javascript', 'application/x-javascript', 'application/ecmascript' ], mode: javascriptLanguage, ext: [ 'js' ], alias: [ 'ecmascript', 'js', 'node' ] },
    { name: 'JSON', mime: 'application/json', mimes: [ 'application/json', 'application/x-json' ], mode: jsonLanguage, ext: [ 'json', 'map' ], alias: [ 'json5' ] },
    { name: 'Lua', mime: 'text/x-lua', mode: lua, ext: [ 'lua' ] },
    { name: 'Markdown', mime: 'text/x-markdown', mode: markdown({ defaultCodeLanguage: markdownLanguage }), ext: [ 'markdown', 'md', 'mkd' ] },
    { name: 'MariaDB', mime: 'text/x-mariadb', mode: sql({ dialect: MariaSQL }) },
    { name: 'MS SQL', mime: 'text/x-mssql', mode: sql({ dialect: MSSQL }) },
    { name: 'MySQL', mime: 'text/x-mysql', mode: sql({ dialect: MySQL }) },
    { name: 'Plain Text', mime: 'text/plain', mode: undefined, ext: [ 'txt', 'text', 'conf', 'def', 'list', 'log' ] },
    { name: 'PostgreSQL', mime: 'text/x-pgsql', mode: sql({ dialect: PostgreSQL }) },
    { name: 'Properties', mime: 'text/x-properties', mode: properties, ext: [ 'properties', 'ini', 'in' ], alias: [ 'ini', 'properties' ] },
    { name: 'Python', mime: 'text/x-python', mode: python, ext: [ 'BUILD', 'bzl', 'py', 'pyw' ], file: /^(BUCK|BUILD)$/ },
    { name: 'Ruby', mime: 'text/x-ruby', mode: ruby, ext: [ 'rb' ], alias: [ 'jruby', 'macruby', 'rake', 'rb', 'rbx' ] },
    { name: 'Rust', mime: 'text/x-rustsrc', mode: rustLanguage, ext: [ 'rs' ] },
    { name: 'Sass', mime: 'text/x-sass', mode: cssLanguage, ext: [ 'sass' ] },
    { name: 'SCSS', mime: 'text/x-scss', mode: cssLanguage, ext: [ 'scss' ] },
    { name: 'Shell', mime: 'text/x-sh', mimes: [ 'text/x-sh', 'application/x-sh' ], mode: shell, ext: [ 'sh', 'ksh', 'bash' ], alias: [ 'bash', 'sh', 'zsh' ], file: /^PKGBUILD$/ },
    { name: 'SQL', mime: 'text/x-sql', mode: sql({ dialect: StandardSQL }), ext: [ 'sql' ] },
    { name: 'SQLite', mime: 'text/x-sqlite', mode: sql({ dialect: SQLite }) },
    { name: 'TOML', mime: 'text/x-toml', mode: toml, ext: [ 'toml' ] },
    { name: 'TypeScript', mime: 'application/typescript', mode: typescriptLanguage, ext: [ 'ts' ], alias: [ 'ts' ] },
    { name: 'XML', mime: 'application/xml', mimes: [ 'application/xml', 'text/xml' ], mode: xmlLanguage, ext: [ 'xml', 'xsl', 'xsd', 'svg' ], alias: [ 'rss', 'wsdl', 'xsd' ] },
    { name: 'YAML', mime: 'text/x-yaml', mimes: [ 'text/x-yaml', 'text/yaml' ], mode: yaml, ext: [ 'yaml', 'yml' ], alias: [ 'yml' ] },
];

export const modeToExtension = (m: EditorMode): Extension => {
    if (m instanceof LanguageSupport) {
        return m;
    }

    if (m instanceof LRLanguage) {
        return m;
    }

    return StreamLanguage.define(m);
};

const findModeByFilename = (filename: string): Mode => {
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

    const plainText = modes.find(m => m.mime === 'text/plain');
    if (plainText === undefined) {
        throw new Error('failed to find \'text/plain\' mode');
    }
    return plainText;
};

const findLanguageExtensionByMode = (mode: Mode): Extension => {
    if (mode.mode === undefined) {
        return [];
    }
    return modeToExtension(mode.mode);
};

const defaultExtensions: Extension = [
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
        indentWithTab,
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
    style?: React.CSSProperties;
    overrides?: TwStyle;

    initialContent?: string;
    extensions?: Extension[];
    mode?: EditorMode;

    filename?: string;
    onModeChanged?: (mode: Mode) => void;
    fetchContent?: (callback: () => Promise<string>) => void;
    onContentSaved?: () => void;
}

export default ({ className, style, overrides, initialContent, extensions, mode, filename, onModeChanged, fetchContent, onContentSaved }: Props) => {
    const [ languageConfig ] = useState<Compartment>(new Compartment());
    const [ keybinds ] = useState<Compartment>(new Compartment());
    const [ state ] = useState<EditorState>(EditorState.create({
        doc: initialContent,
        extensions: [
            ...defaultExtensions,
            ...(extensions !== undefined ? extensions : []),
            languageConfig.of(mode !== undefined ? modeToExtension(mode) : findLanguageExtensionByMode(findModeByFilename(filename || ''))),
            keybinds.of([]),
        ],
    }));
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

    // This useEffect is required to send the proper mode back to the parent element
    // due to the initial language being set with EditorState#create, rather than in
    // an useEffect like this one, or one watching `filename`.
    useEffect(() => {
        if (onModeChanged === undefined) {
            return;
        }

        onModeChanged(findModeByFilename(filename || ''));
    }, []);

    useEffect(() => {
        if (view === undefined) {
            return;
        }

        if (mode === undefined) {
            return;
        }

        view.dispatch({
            effects: languageConfig.reconfigure(modeToExtension(mode)),
        });
    }, [ mode ]);

    useEffect(() => {
        if (view === undefined) {
            return;
        }

        if (filename === undefined) {
            return;
        }

        const mode = findModeByFilename(filename || '');

        view.dispatch({
            effects: languageConfig.reconfigure(findLanguageExtensionByMode(mode)),
        });

        if (onModeChanged !== undefined) {
            onModeChanged(mode);
        }
    }, [ filename ]);

    useEffect(() => {
        if (view === undefined) {
            return;
        }

        view.dispatch({
            changes: { from: 0, insert: initialContent },
        });
    }, [ initialContent ]);

    useEffect(() => {
        if (fetchContent === undefined) {
            return;
        }

        if (!view) {
            fetchContent(() => Promise.reject(new Error('no editor session has been configured')));
            return;
        }

        if (onContentSaved !== undefined) {
            view.dispatch({
                effects: keybinds.reconfigure(keymap.of([
                    {
                        key: 'Mod-s',
                        run: () => {
                            onContentSaved();
                            return true;
                        },
                    },
                ])),
            });
        }

        fetchContent(() => Promise.resolve(view.state.doc.toString()));
    }, [ view, fetchContent, onContentSaved ]);

    return (
        <EditorContainer className={className} style={style} overrides={overrides} ref={ref}/>
    );
};
