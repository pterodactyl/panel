import { Play, RotateCcw, Square, Zap } from 'lucide-react';
import { Button, Dialog } from '@pterodactyl/sdk';
import { Slot, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Compact power controls. The model says what the user may do and what the server allows right now;
 * stopping a server that is already stopping kills it, so that asks first.
 */
export default function Power({ model }: ViewProps<'server.power'>) {
    const { permissions, enabled, actions } = model;

    return (
        <div className='flex items-center gap-2'>
            <Slot name='server.console.power.before' data={model.server} />
            {permissions.start && (
                <Button size='small' color='green' disabled={!enabled.start} onClick={actions.start}>
                    <Play className='size-4' aria-hidden /> Start
                </Button>
            )}
            {permissions.restart && (
                <Button size='small' color='grey' disabled={!enabled.restart} onClick={actions.restart}>
                    <RotateCcw className='size-4' aria-hidden /> Restart
                </Button>
            )}
            {permissions.stop &&
                (model.killable ? (
                    <Dialog.ConfirmTrigger
                        title='Kill the server process?'
                        confirm='Kill'
                        trigger={({ onClick }) => (
                            <Button size='small' color='red' disabled={!enabled.kill} onClick={onClick}>
                                <Zap className='size-4' aria-hidden /> Kill
                            </Button>
                        )}
                        onConfirmed={(_event, close) => {
                            close();
                            actions.kill();
                        }}
                    >
                        Killing a server can corrupt its data.
                    </Dialog.ConfirmTrigger>
                ) : (
                    <Button size='small' color='red' disabled={!enabled.stop} onClick={actions.stop}>
                        <Square className='size-4' aria-hidden /> Stop
                    </Button>
                ))}
            <Slot name='server.console.power.after' data={model.server} />
        </div>
    );
}
