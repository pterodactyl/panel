import { useEffect, useMemo, useRef, useState, useSyncExternalStore, type ComponentType, type RefObject } from 'react';
import type { Terminal } from 'ghostty-web';
import { useCurrentServerIdentifier } from '@/api/server/queries';
import { SocketRequest } from '@/components/server/events';
import { cn } from '@/lib/cn';
import type { Websocket } from '@/plugins/Websocket';
import { usePermissions } from '@/plugins/usePermissions';
import { usePersistedState } from '@/plugins/usePersistedState';
import { useSocketConnected, useSocketInstance } from '@/state/server';
import type { ServerConsoleCommand, ServerTerminalModel, TerminalCanvasProps } from '@/views/server/console';
import ConsoleScrollbar from './ConsoleScrollbar';
import type { TerminalScrollStore } from './TerminalScrollStore';
import { useConsoleSocketLogs } from './useConsoleSocketLogs';
import { useTerminal } from './useTerminal';

const HISTORY_LENGTH = 32;
const noHistory: string[] = [];

interface TerminalSession {
    ref: (element: HTMLDivElement | null) => void;
    scroll: TerminalScrollStore;
    terminal: RefObject<Terminal | null>;
}

/** The canvas of one terminal, with the scrollbar drawn in the gutter beside it. */
function createTerminalCanvas({ ref, scroll, terminal }: TerminalSession): ComponentType<TerminalCanvasProps> {
    return function TerminalCanvas({ className }: TerminalCanvasProps) {
        return (
            <div className={cn('relative h-full w-full', className)}>
                <div className='h-full w-full' ref={ref} />
                <ConsoleScrollbar scroll={scroll} terminal={terminal} />
            </div>
        );
    };
}

/** The command line: its value, the history it steps through, and sending. */
function useConsoleCommand(serverId: string, socket: Websocket | null, connected: boolean): ServerConsoleCommand {
    const [allowed] = usePermissions(['control.console']);
    const [stored, setHistory] = usePersistedState<string[]>(`${serverId}:command_history`, []);
    const [value, setValue] = useState('');
    const index = useRef(-1);
    const history = stored ?? noHistory;

    return useMemo(() => {
        const step = (next: number) => {
            index.current = next;
            setValue(history[next] || '');
        };

        return {
            allowed,
            disabled: !socket || !connected,
            value,
            history,
            setValue,
            previous: () => step(Math.min(index.current + 1, history.length - 1)),
            next: () => step(Math.max(index.current - 1, -1)),
            submit: () => {
                if (!value.length) {
                    return;
                }

                setHistory((previous) => [value, ...(previous ?? [])].slice(0, HISTORY_LENGTH));
                index.current = -1;
                socket?.send(SocketRequest.SEND_COMMAND, value);
                setValue('');
            },
        };
    }, [allowed, socket, connected, value, history, setHistory]);
}

/** The server's console: a terminal fed by the websocket, and the command line. */
export function useServerTerminal(): ServerTerminalModel {
    const { ref, retry, scroll, terminal, terminalError, terminalReady } = useTerminal();
    // Smooth scrolling can settle a fraction of a line above the bottom, which still counts as at the bottom.
    const scrolledUp = useSyncExternalStore(scroll.subscribe, () => scroll.getSnapshot().viewportY > 0.5);
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const command = useConsoleCommand(useCurrentServerIdentifier() ?? '', instance, connected);
    const [Terminal] = useState(() => createTerminalCanvas({ ref, scroll, terminal }));

    useConsoleSocketLogs({ connected, instance, terminal, terminalReady });

    return useMemo(
        () => ({
            Terminal,
            loading: !terminalError && (!connected || !terminalReady),
            failed: !!terminalError,
            retry,
            scrolledUp,
            scrollToBottom: () => terminal.current?.scrollToBottom(),
            command,
        }),
        [Terminal, terminalError, connected, terminalReady, retry, scrolledUp, terminal, command]
    );
}

/** A terminal showing fixed output, for fixtures. */
export function createSampleTerminal(lines: readonly string[]): ComponentType<TerminalCanvasProps> {
    return function SampleTerminal({ className }: TerminalCanvasProps) {
        const { ref, scroll, terminal, terminalReady } = useTerminal();
        const [Canvas] = useState(() => createTerminalCanvas({ ref, scroll, terminal }));

        useEffect(() => {
            if (terminalReady) {
                for (const line of lines) {
                    terminal.current?.writeln(line);
                }
            }
        }, [terminal, terminalReady]);

        return <Canvas className={className} />;
    };
}
