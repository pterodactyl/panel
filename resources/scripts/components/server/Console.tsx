import React, { createRef, useEffect, useRef } from 'react';
import { Terminal } from 'xterm';
import * as TerminalFit from 'xterm/lib/addons/fit/fit';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import { connect } from 'formik';

const theme = {
    background: 'transparent',
    cursor: 'transparent',
    black: '#000000',
    red: '#E54B4B',
    green: '#9ECE58',
    yellow: '#FAED70',
    blue: '#396FE2',
    magenta: '#BB80B3',
    cyan: '#2DDAFD',
    white: '#d0d0d0',
    brightBlack: 'rgba(255, 255, 255, 0.2)',
    brightRed: '#FF5370',
    brightGreen: '#C3E88D',
    brightYellow: '#FFCB6B',
    brightBlue: '#82AAFF',
    brightMagenta: '#C792EA',
    brightCyan: '#89DDFF',
    brightWhite: '#ffffff',
};

export default () => {
    const { instance, connected } = useStoreState((state: State<ApplicationState>) => state.server.socket);

    const ref = createRef<HTMLDivElement>();
    const terminal = useRef(new Terminal({
        disableStdin: true,
        cursorStyle: 'underline',
        allowTransparency: true,
        fontSize: 12,
        fontFamily: 'Menlo, Monaco, Consolas, monospace',
        rows: 30,
        theme: theme,
    }));

    const handleServerLog = (lines: string[]) => lines.forEach(data => {
        return data.split(/\n/g).forEach(line => terminal.current.writeln(line + '\u001b[0m'));
    });

    const handleConsoleOutput = (line: string) => terminal.current.writeln(line.replace(/(?:\r\n|\r|\n)$/im, '') + '\u001b[0m');

    useEffect(() => {
        ref.current && terminal.current.open(ref.current);

        // @see https://github.com/xtermjs/xterm.js/issues/2265
        // @see https://github.com/xtermjs/xterm.js/issues/2230
        TerminalFit.fit(terminal.current);
    }, []);

    useEffect(() => {
        if (connected && instance) {
            instance.addListener('server log', handleServerLog);
            instance.addListener('console output', handleConsoleOutput);
            instance.send('send logs');
        }
    }, [connected]);

    useEffect(() => () => {
        if (instance) {
            instance.removeListener('server log', handleServerLog);
            instance.removeListener('console output', handleConsoleOutput);
        }
    });

    return (
        <div className={'text-xs font-mono relative'}>
            <SpinnerOverlay visible={!connected} large={true}/>
            <div
                className={'rounded-t p-2 bg-black overflow-scroll w-full'}
                style={{
                    minHeight: '16rem',
                    maxHeight: '64rem',
                }}
            >
                <div id={'terminal'} ref={ref}/>
            </div>
            <div className={'rounded-b bg-neutral-900 text-neutral-100 flex'}>
                <div className={'flex-no-shrink p-2 font-bold'}>$</div>
                <div className={'w-full'}>
                    <input type={'text'} className={'bg-transparent text-neutral-100 p-2 pl-0 w-full'}/>
                </div>
            </div>
        </div>
    );
};
