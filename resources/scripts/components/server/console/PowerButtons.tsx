import React from 'react';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';
import { useServerStatus, useSocketConnected, useSocketInstance } from '@/state/server';
import type { PowerAction } from '@/components/server/console/types';
import { Dialog } from '@/components/elements/dialog';

interface PowerButtonProps {
    className?: string;
}

export default function PowerButtons({ className }: PowerButtonProps) {
    const status = useServerStatus();
    const instance = useSocketInstance();
    const connected = useSocketConnected();

    const killable = status === 'stopping';
    const onButtonClick = (action: PowerAction, e: React.MouseEvent<HTMLButtonElement, MouseEvent>): void => {
        e.preventDefault();

        if (instance) {
            instance.send('set state', action);
        }
    };

    return (
        <div className={className}>
            <Can action={'control.start'}>
                <Button
                    className={'flex-1'}
                    disabled={!connected || status !== 'offline'}
                    onClick={onButtonClick.bind(null, 'start')}
                >
                    Start
                </Button>
            </Can>
            <Can action={'control.restart'}>
                <Button.Text
                    className={'flex-1'}
                    disabled={!connected || !status}
                    onClick={onButtonClick.bind(null, 'restart')}
                >
                    Restart
                </Button.Text>
            </Can>
            <Can action={'control.stop'}>
                {killable ? (
                    <Dialog.ConfirmTrigger
                        key={'killable'}
                        hideCloseIcon
                        title={'Forcibly Stop Process'}
                        confirm={'Continue'}
                        trigger={({ onClick }) => (
                            <Button.Danger className={'flex-1'} disabled={!connected} onClick={onClick}>
                                Kill
                            </Button.Danger>
                        )}
                        onConfirmed={(event, close) => {
                            close();
                            onButtonClick('kill', event);
                        }}
                    >
                        Forcibly stopping a server can lead to data corruption.
                    </Dialog.ConfirmTrigger>
                ) : (
                    <Button.Danger
                        key={'stoppable'}
                        className={'flex-1'}
                        disabled={!connected || status === 'offline'}
                        onClick={onButtonClick.bind(null, 'stop')}
                    >
                        Stop
                    </Button.Danger>
                )}
            </Can>
        </div>
    );
}
