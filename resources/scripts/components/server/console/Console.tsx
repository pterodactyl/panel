import { useCurrentServerIdentifier } from '@/api/server/queries';
import Button from '@/components/elements/Button';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useSocketConnected, useSocketInstance } from '@/state/server';
import { usePermissions } from '@/plugins/usePermissions';
import { cn } from '@/lib/cn';
import { ChevronsDown, ChevronsRight } from 'lucide-react';
import { useConsoleCommandHistory } from '@/components/server/console/useConsoleCommandHistory';
import { useConsoleSocketLogs } from '@/components/server/console/useConsoleSocketLogs';
import { useTerminal } from '@/components/server/console/useTerminal';

const terminalClass = 'relative flex flex-col w-full';

const overflowsContainerClass = '-ml-4 w-[calc(100%+2rem)] sm:ml-0 sm:w-full';

const terminalContainerClass = [
    'relative min-h-[16rem] flex-1 rounded-t-sm bg-terminal p-1 font-mono text-sm caret-transparent sm:p-2',
    '[&_#terminal]:h-full [&_#terminal::-webkit-scrollbar-track]:w-2 [&_#terminal::-webkit-scrollbar-thumb]:bg-terminal',
].join(' ');

const commandInputClass = [
    'peer relative w-full bg-terminal px-2 py-2 pr-4 pl-10 font-mono text-sm text-foreground sm:rounded-b-sm',
    'border-0 border-b-2 border-transparent transition-colors duration-100',
    'outline-hidden focus:ring-0 focus-visible:outline-hidden active:border-accent focus:border-accent',
].join(' ');

const commandIconClass =
    'absolute left-0 top-0 z-10 flex h-full select-none items-center px-3 text-foreground transition-colors duration-100 peer-focus:text-foreground peer-focus:animate-pulse';

export default function Console() {
    const { ref, retry, scrolledUp, setScrolledUp, terminal, terminalError, terminalReady } = useTerminal();
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const [canSendCommands] = usePermissions(['control.console']);
    const serverId = useCurrentServerIdentifier() ?? '';

    useConsoleSocketLogs({ connected, instance, terminal, terminalReady });
    const handleCommandKeyDown = useConsoleCommandHistory(serverId, instance);

    return (
        <div className={terminalClass}>
            <SpinnerOverlay visible={!terminalError && (!connected || !terminalReady)} size={'large'} />
            {terminalError && (
                <div
                    role={'alert'}
                    className={
                        'absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-sm bg-terminal p-4 text-center text-sm text-foreground'
                    }
                >
                    <p>The console could not be loaded.</p>
                    <Button.Text size={'small'} onClick={retry}>
                        Try again
                    </Button.Text>
                </div>
            )}
            <div
                className={cn(terminalContainerClass, overflowsContainerClass, {
                    'rounded-b-sm': !canSendCommands,
                })}
            >
                <div className={'h-full w-full'} ref={ref} />
                {scrolledUp && (
                    <button
                        type={'button'}
                        onClick={() => {
                            terminal.current?.scrollToBottom();
                            setScrolledUp(false);
                        }}
                        aria-label={'Scroll to bottom'}
                        className={
                            // right-5 clears Ghostty's ~14px canvas scrollbar.
                            'absolute bottom-3 right-5 z-10 rounded-full bg-popover/90 p-2 text-foreground shadow-lg transition-colors hover:bg-popover'
                        }
                    >
                        <ChevronsDown className={'w-4 h-4'} />
                    </button>
                )}
            </div>
            {canSendCommands && (
                <div className={cn('relative', overflowsContainerClass)}>
                    <input
                        className={commandInputClass}
                        type={'text'}
                        placeholder={'Type a command...'}
                        aria-label={'Console command input.'}
                        disabled={!instance || !connected}
                        onKeyDown={handleCommandKeyDown}
                        autoCorrect={'off'}
                        autoCapitalize={'none'}
                    />
                    <div className={commandIconClass}>
                        <ChevronsRight className={'w-4 h-4'} />
                    </div>
                </div>
            )}
        </div>
    );
}
