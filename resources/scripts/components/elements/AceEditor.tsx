import React, { useCallback, useEffect, useState, lazy } from 'react';
import useRouter from 'use-react-router';
import { ServerContext } from '@/state/server';
import ace, { Editor } from 'brace';
import getFileContents from '@/api/server/files/getFileContents';
import styled from 'styled-components';

// @ts-ignore
require('brace/ext/modelist');
require('ayu-ace/mirage');

const EditorContainer = styled.div`
    min-height: 16rem;
    height: calc(100vh - 16rem);
    ${tw`relative`};
    
    #editor {
        ${tw`rounded h-full`};
    }
`;

const modes: { [k: string]: string } = {
    // eslint-disable-next-line @typescript-eslint/camelcase
    assembly_x86: 'Assembly (x86)',
    // eslint-disable-next-line @typescript-eslint/camelcase
    c_cpp: 'C++',
    coffee: 'Coffeescript',
    css: 'CSS',
    dockerfile: 'Dockerfile',
    golang: 'Go',
    html: 'HTML',
    ini: 'Ini',
    java: 'Java',
    javascript: 'Javascript',
    json: 'JSON',
    kotlin: 'Kotlin',
    lua: 'Luascript',
    perl: 'Perl',
    php: 'PHP',
    properties: 'Properties',
    python: 'Python',
    ruby: 'Ruby',
    // eslint-disable-next-line @typescript-eslint/camelcase
    plain_text: 'Plaintext',
    toml: 'TOML',
    typescript: 'Typescript',
    xml: 'XML',
    yaml: 'YAML',
};

Object.keys(modes).forEach(mode => require(`brace/mode/${mode}`));

export interface Props {
    style?: React.CSSProperties;
    initialContent?: string;
    initialModePath?: string;
    fetchContent: (callback: () => Promise<string>) => void;
    onContentSaved: (content: string) => void;
}

export default ({ style, initialContent, initialModePath, fetchContent, onContentSaved }: Props) => {
    const [ mode, setMode ] = useState('ace/mode/plain_text');

    const [ editor, setEditor ] = useState<Editor>();
    const ref = useCallback(node => {
        if (node) {
            setEditor(ace.edit('editor'));
        }
    }, []);

    useEffect(() => {
        editor && editor.session.setMode(mode);
    }, [editor, mode]);

    useEffect(() => {
        editor && editor.session.setValue(initialContent || '');
    }, [ editor, initialContent ]);

    useEffect(() => {
        if (initialModePath) {
            const modelist = ace.acequire('ace/ext/modelist');
            if (modelist) {
                setMode(modelist.getModeForPath(initialModePath).mode);
            }
        }
    }, [ initialModePath ]);

    useEffect(() => {
        if (!editor) {
            fetchContent(() => Promise.reject(new Error('no editor session has been configured')));
            return;
        }

        editor.setTheme('ace/theme/ayu-mirage');

        editor.$blockScrolling = Infinity;
        editor.container.style.lineHeight = '1.375rem';
        editor.container.style.fontWeight = '500';
        editor.renderer.updateFontSize();
        editor.renderer.setShowPrintMargin(false);
        editor.session.setTabSize(4);
        editor.session.setUseSoftTabs(true);

        editor.commands.addCommand({
            name: 'Save',
            bindKey: { win: 'Ctrl-s', mac: 'Command-s' },
            exec: (editor: Editor) => onContentSaved(editor.session.getValue()),
        });

        fetchContent(() => Promise.resolve(editor.session.getValue()));
    }, [ editor, fetchContent, onContentSaved ]);

    return (
        <EditorContainer style={style}>
            <div id={'editor'} ref={ref}/>
            <div className={'absolute pin-r pin-b z-50'}>
                <div className={'m-3 rounded bg-neutral-900 border border-black'}>
                    <select
                        className={'input-dark'}
                        value={mode.split('/').pop()}
                        onChange={e => setMode(`ace/mode/${e.currentTarget.value}`)}
                    >
                        {
                            Object.keys(modes).map(key => (
                                <option key={key} value={key}>{modes[key]}</option>
                            ))
                        }
                    </select>
                </div>
            </div>
        </EditorContainer>
    );
};
