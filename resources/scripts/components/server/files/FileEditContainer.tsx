import React, { useCallback, useEffect, useState } from 'react';
import useRouter from 'use-react-router';
import { ServerContext } from '@/state/server';
import getFileContents from '@/api/server/files/getFileContents';
import ace, { Editor } from 'brace';
import styled from 'styled-components';

const EditorContainer = styled.div`
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
    text: 'Plaintext',
    toml: 'TOML',
    typescript: 'Typescript',
    xml: 'XML',
    yaml: 'YAML',
};

export default () => {
    const { location: { hash } } = useRouter();
    const [ content, setContent ] = useState('');
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const [ editor, setEditor ] = useState<Editor>();
    const ref = useCallback(node => {
        if (node) {
            setEditor(ace.edit('editor'));
        }
    }, []);

    useEffect(() => {
        Object.keys(modes).forEach(mode => {
            import(/* webpackMode: "lazy-once", webpackChunkName: "ace_mode" */`brace/mode/${mode}`);
        });
    }, []);

    useEffect(() => {
        getFileContents(uuid, hash.replace(/^#/, ''))
            .then(setContent)
            .catch(error => console.error(error));
    }, [ uuid, hash ]);

    useEffect(() => {
        editor && editor.session.setValue(content);
    }, [ editor, content ]);

    useEffect(() => {
        if (!editor) {
            return;
        }

        require('ayu-ace/mirage');
        editor.setTheme('ace/theme/ayu-mirage');

        editor.$blockScrolling = Infinity;
        editor.container.style.lineHeight = '1.375rem';
        editor.container.style.fontWeight = '500';
        editor.renderer.updateFontSize();
        editor.renderer.setShowPrintMargin(false);
        editor.session.setTabSize(4);
        editor.session.setUseSoftTabs(true);
    }, [ editor ]);

    return (
        <div className={'my-10'}>
            <EditorContainer>
                <div id={'editor'} ref={ref}/>
                <div className={'absolute pin-r pin-t z-50'}>
                    <div className={'m-3 rounded bg-neutral-900 border border-black'}>
                        <select
                            className={'input-dark'}
                            onChange={e => {
                                if (editor) {
                                    editor.session.setMode(`ace/mode/${e.currentTarget.value}`);
                                }
                            }}
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
        </div>
    );
};
