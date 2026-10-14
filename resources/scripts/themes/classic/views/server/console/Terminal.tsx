import type { KeyboardEvent } from 'react';
import { ChevronsDown, ChevronsRight } from 'lucide-react';
import { Button, cn, SpinnerOverlay } from '@/sdk';
import type { ServerConsoleCommand, ViewProps } from '@/sdk/theme';

const overflowsContainerClass = '-ml-4 w-[calc(100%+2rem)] sm:ml-0 sm:w-full';

const terminalContainerClass =
    'relative min-h-[16rem] flex-1 rounded-t-sm bg-terminal p-1 font-mono text-sm caret-transparent sm:p-2';

const commandInputClass = [
    'peer relative w-full bg-terminal px-2 py-2 pr-4 pl-10 font-mono text-sm text-foreground sm:rounded-b-sm',
    'border-0 border-b-2 border-transparent transition-colors duration-100',
    'outline-hidden focus:ring-0 focus-visible:outline-hidden active:border-accent focus:border-accent',
].join(' ');

const commandIconClass =
    'absolute left-0 top-0 z-10 flex h-full select-none items-center px-3 text-foreground transition-colors duration-100 peer-focus:text-foreground peer-focus:animate-pulse';

function onCommandKey(command: ServerConsoleCommand, event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'ArrowUp') {
        // Keeps the cursor at the end of the recalled command.
        event.preventDefault();
        command.previous();
    }

    if (event.key === 'ArrowDown') {
        command.next();
    }

    if (event.key === 'Enter') {
        command.submit();
    }
}

export default function Terminal({ model, className }: ViewProps<'server.terminal'>) {
    const { command } = model;

    return (
        <div className={cn('relative flex flex-col w-full', className)}>
            <SpinnerOverlay visible={model.loading} size='large' />
            {model.failed && (
                <div
                    role='alert'
                    className='absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-sm bg-terminal p-4 text-center text-sm text-foreground'
                >
                    <p>The console could not be loaded.</p>
                    <Button.Text size='small' onClick={model.retry}>
                        Try again
                    </Button.Text>
                </div>
            )}
            <div className={cn(terminalContainerClass, overflowsContainerClass, !command.allowed && 'rounded-b-sm')}>
                <model.Terminal />
                {model.scrolledUp && (
                    <button
                        type='button'
                        onClick={model.scrollToBottom}
                        aria-label='Scroll to bottom'
                        className={
                            // right-5 clears the scrollbar in the terminal's right gutter.
                            'absolute bottom-3 right-5 z-10 rounded-full bg-popover/90 p-2 text-foreground shadow-lg transition-colors hover:bg-popover'
                        }
                    >
                        <ChevronsDown className='w-4 h-4' />
                    </button>
                )}
            </div>
            {command.allowed && (
                <div className={cn('relative', overflowsContainerClass)}>
                    <input
                        className={commandInputClass}
                        type='text'
                        placeholder='Type a command...'
                        aria-label='Console command input.'
                        disabled={command.disabled}
                        value={command.value}
                        onChange={(event) => command.setValue(event.currentTarget.value)}
                        onKeyDown={(event) => onCommandKey(command, event)}
                        autoCorrect='off'
                        autoCapitalize='none'
                    />
                    <div className={commandIconClass}>
                        <ChevronsRight className='w-4 h-4' />
                    </div>
                </div>
            )}
        </div>
    );
}
