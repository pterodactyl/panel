import React, { useCallback, useEffect, useState } from 'react';
import ace, { Editor } from 'brace';
import styled from 'styled-components/macro';
import tw from 'twin.macro';
import modes from '@/modes';

// @ts-ignore
require('brace/ext/modelist');
require('ayu-ace/mirage');

const EditorContainer = styled.div`
    min-height: 16rem;
    height: calc(100vh - 20rem);
    ${tw`relative`};
    
    #editor {
        ${tw`rounded h-full`};
    }
`;

Object.keys(modes).forEach(mode => require(`brace/mode/${mode}`));
const modelist = ace.acequire('ace/ext/modelist');

export interface Props {
    style?: React.CSSProperties;
    initialContent?: string;
    mode: string;
    filename?: string;
    onModeChanged: (mode: string) => void;
    fetchContent: (callback: () => Promise<string>) => void;
    onContentSaved: () => void;
}

export default ({ style, initialContent, filename, mode, fetchContent, onContentSaved, onModeChanged }: Props) => {
    const [ editor, setEditor ] = useState<Editor>();
    const ref = useCallback(node => {
        if (node) setEditor(ace.edit('editor'));
    }, []);

    useEffect(() => {
        if (modelist && filename) {
            onModeChanged(modelist.getModeForPath(filename).mode.replace(/^ace\/mode\//, ''));
        }
    }, [ filename ]);

    useEffect(() => {
        editor && editor.session.setMode(`ace/mode/${mode}`);
    }, [ editor, mode ]);

    useEffect(() => {
        editor && editor.session.setValue(initialContent || '');
    }, [ editor, initialContent ]);

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
            exec: () => onContentSaved(),
        });

        fetchContent(() => Promise.resolve(editor.session.getValue()));
    }, [ editor, fetchContent, onContentSaved ]);

    return (
        <EditorContainer style={style}>
            <div id={'editor'} ref={ref}/>
        </EditorContainer>
    );
};
