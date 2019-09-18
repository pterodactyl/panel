import React, { createRef, useEffect } from 'react';
import { Terminal } from 'xterm';
import * as TerminalFit from 'xterm/lib/addons/fit/fit';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ServerContext } from '@/state/server';

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

const terminal = new Terminal({
    disableStdin: true,
    cursorStyle: 'underline',
    allowTransparency: true,
    fontSize: 12,
    fontFamily: 'Menlo, Monaco, Consolas, monospace',
    rows: 30,
    theme: theme,
});

export default () => {
    const ref = createRef<HTMLDivElement>();
    const connected = ServerContext.useStoreState(state => state.socket.connected);
    const instance = ServerContext.useStoreState(state => state.socket.instance);

    const handleConsoleOutput = (line: string) => terminal.writeln(
        line.replace(/(?:\r\n|\r|\n)$/im, '') + '\u001b[0m',
    );

    useEffect(() => {
        if (ref.current) {
            terminal.open(ref.current);

            // @see https://github.com/xtermjs/xterm.js/issues/2265
            // @see https://github.com/xtermjs/xterm.js/issues/2230
            TerminalFit.fit(terminal);
        }
    }, [ ref.current ]);

    useEffect(() => {
        if (connected && instance) {
            terminal.clear();

            instance
                // .addListener('stats', data => console.log(JSON.parse(data)))
                .addListener('console output', handleConsoleOutput);

            instance.send('send logs');
        }

        return () => {
            instance && instance
                .removeAllListeners('console output')
                .removeAllListeners('stats');
        };
    }, [ connected, instance ]);

    return (
        <div className={'text-xs font-mono relative'}>
            <SpinnerOverlay visible={!connected} size={'large'}/>
            <div
                className={'rounded-t p-2 bg-black overflow-scroll w-full'}
                style={{
                    minHeight: '16rem',
                    maxHeight: '32rem',
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
