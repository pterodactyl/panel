import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ITerminalOptions, Terminal } from 'xterm';
import * as TerminalFit from 'xterm/lib/addons/fit/fit';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ServerContext } from '@/state/server';
import styled from 'styled-components';

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

const terminalProps: ITerminalOptions = {
    disableStdin: true,
    cursorStyle: 'underline',
    allowTransparency: true,
    fontSize: 12,
    fontFamily: 'Menlo, Monaco, Consolas, monospace',
    rows: 30,
    theme: theme,
};

const TerminalDiv = styled.div`
    &::-webkit-scrollbar {
        width: 8px;
    }

    &::-webkit-scrollbar-thumb {
        ${tw`bg-neutral-900`};
    }
`;

export default () => {
    const [ terminalElement, setTerminalElement ] = useState<HTMLDivElement | null>(null);
    const useRef = useCallback(node => setTerminalElement(node), []);
    const terminal = useMemo(() => new Terminal({ ...terminalProps }), []);
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    const handleConsoleOutput = (line: string) => terminal.writeln(
        line.replace(/(?:\r\n|\r|\n)$/im, '') + '\u001b[0m',
    );

    const handleDaemonErrorOutput = (line: string) => terminal.writeln(
        '\u001b[1m\u001b[41m[Internal] ' + line.replace(/(?:\r\n|\r|\n)$/im, '') + '\u001b[0m',
    );

    const handlePowerChangeEvent = (state: string) => terminal.writeln(
        '\u001b[1m\u001b[33m[Status Change] Server marked as ' + state + '...\u001b[0m',
    );

    const handleCommandKeydown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key !== 'Enter' || (e.key === 'Enter' && e.currentTarget.value.length < 1)) {
            return;
        }

        instance && instance.send('send command', e.currentTarget.value);
        e.currentTarget.value = '';
    };

    useEffect(() => {
        if (connected && terminalElement && !terminal.element) {
            terminal.open(terminalElement);

            // @see https://github.com/xtermjs/xterm.js/issues/2265
            // @see https://github.com/xtermjs/xterm.js/issues/2230
            TerminalFit.fit(terminal);
        }
    }, [ terminal, connected, terminalElement ]);

    useEffect(() => {
        if (connected && instance) {
            terminal.clear();

            instance.addListener('status', handlePowerChangeEvent);
            instance.addListener('console output', handleConsoleOutput);
            instance.addListener('daemon error', handleDaemonErrorOutput);
            instance.send('send logs');
        }

        return () => {
            instance && instance.removeListener('console output', handleConsoleOutput)
                .removeListener('daemon error', handleDaemonErrorOutput)
                .removeListener('status', handlePowerChangeEvent);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ connected, instance ]);

    return (
        <div className={'text-xs font-mono relative'}>
            <SpinnerOverlay visible={!connected} size={'large'}/>
            <div
                className={'rounded-t p-2 bg-black w-full'}
                style={{
                    minHeight: '16rem',
                    maxHeight: '32rem',
                }}
            >
                <TerminalDiv id={'terminal'} ref={useRef}/>
            </div>
            <div className={'rounded-b bg-neutral-900 text-neutral-100 flex'}>
                <div className={'flex-no-shrink p-2 font-bold'}>$</div>
                <div className={'w-full'}>
                    <input
                        type={'text'}
                        disabled={!instance || !connected}
                        className={'bg-transparent text-neutral-100 p-2 pl-0 w-full'}
                        onKeyDown={e => handleCommandKeydown(e)}
                    />
                </div>
            </div>
        </div>
    );
};
