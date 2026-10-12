import type { KeyboardEvent } from 'react';
import { Button, Spinner } from '@pterodactyl/sdk';
import type { ServerConsoleCommand, ViewProps } from '@pterodactyl/sdk/theme';

function onKey(command: ServerConsoleCommand, event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Enter') {
        command.submit();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        command.previous();
    } else if (event.key === 'ArrowDown') {
        command.next();
    }
}

/** The engine draws the output on `model.Terminal`; the theme sizes it and owns the command line. */
export default function Terminal({ model }: ViewProps<'server.terminal'>) {
    const { command } = model;

    return (
        <section aria-label='Console' className='glass flex h-[30rem] flex-col overflow-hidden rounded-2xl'>
            <div className='relative min-h-0 flex-1 bg-terminal p-3 font-mono text-sm'>
                <model.Terminal />
                {model.loading && (
                    <div className='absolute inset-0 grid place-items-center'>
                        <Spinner size='large' />
                    </div>
                )}
                {model.failed && (
                    <div role='alert' className='absolute inset-0 grid place-items-center gap-2 text-sm'>
                        The console could not be loaded.
                        <Button size='xsmall' color='grey' onClick={model.retry}>
                            Try again
                        </Button>
                    </div>
                )}
                {model.scrolledUp && (
                    <Button size='xsmall' color='grey' className='absolute right-5 bottom-3' onClick={model.scrollToBottom}>
                        Latest output
                    </Button>
                )}
            </div>
            {command.allowed && (
                <input
                    aria-label='Console command'
                    placeholder='Type a command'
                    autoCapitalize='none'
                    autoCorrect='off'
                    disabled={command.disabled}
                    value={command.value}
                    onChange={(event) => command.setValue(event.currentTarget.value)}
                    onKeyDown={(event) => onKey(command, event)}
                    className='border-0 border-t border-(--glass-border) bg-transparent px-4 py-3 font-mono text-sm focus:ring-0'
                />
            )}
        </section>
    );
}
