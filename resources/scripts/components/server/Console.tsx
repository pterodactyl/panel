import React, { createRef } from 'react';
import { Terminal } from 'xterm';
import * as TerminalFit from 'xterm/lib/addons/fit/fit';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { connect } from 'react-redux';
import { Websocket } from '@/plugins/Websocket';
import { ServerStore } from '@/state/server';

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

interface Props {
    connected: boolean;
    instance: Websocket | null;
}

class Console extends React.PureComponent<Readonly<Props>> {
    ref = createRef<HTMLDivElement>();
    terminal = new Terminal({
        disableStdin: true,
        cursorStyle: 'underline',
        allowTransparency: true,
        fontSize: 12,
        fontFamily: 'Menlo, Monaco, Consolas, monospace',
        rows: 30,
        theme: theme,
    });

    componentDidMount () {
        if (this.ref.current) {
            this.terminal.open(this.ref.current);
            this.terminal.clear();

            // @see https://github.com/xtermjs/xterm.js/issues/2265
            // @see https://github.com/xtermjs/xterm.js/issues/2230
            TerminalFit.fit(this.terminal);
        }

        if (this.props.connected && this.props.instance) {
            this.listenForEvents();
        }
    }

    componentDidUpdate (prevProps: Readonly<Readonly<Props>>) {
        if (!prevProps.connected && this.props.connected) {
            this.listenForEvents();
        }
    }

    componentWillUnmount () {
        if (this.props.instance) {
            this.props.instance.removeListener('server log', this.handleServerLog);
            this.props.instance.removeListener('server log', this.handleConsoleOutput);
        }
    }

    listenForEvents () {
        const instance = this.props.instance!;

        instance.addListener('server log', this.handleServerLog);
        instance.addListener('console output', this.handleConsoleOutput);
        instance.send('send logs');
    }

    handleServerLog = (lines: string[]) => lines.forEach(data => {
        return data.split(/\n/g).forEach(line => this.terminal.writeln(line + '\u001b[0m'));
    });

    handleConsoleOutput = (line: string) => this.terminal.writeln(
        line.replace(/(?:\r\n|\r|\n)$/im, '') + '\u001b[0m'
    );

    render () {
        return (
            <div className={'text-xs font-mono relative'}>
                <SpinnerOverlay visible={!this.props.connected} size={'large'}/>
                <div
                    className={'rounded-t p-2 bg-black overflow-scroll w-full'}
                    style={{
                        minHeight: '16rem',
                        maxHeight: '64rem',
                    }}
                >
                    <div id={'terminal'} ref={this.ref}/>
                </div>
                <div className={'rounded-b bg-neutral-900 text-neutral-100 flex'}>
                    <div className={'flex-no-shrink p-2 font-bold'}>$</div>
                    <div className={'w-full'}>
                        <input type={'text'} className={'bg-transparent text-neutral-100 p-2 pl-0 w-full'}/>
                    </div>
                </div>
            </div>
        );
    }
}

export default connect(
    (state: ServerStore) => ({
        connected: state.socket.connected,
        instance: state.socket.instance,
    }),
)(Console);
