import React from 'react';
import tw from 'twin.macro';
import Can from '@/components/elements/Can';
import { ServerContext } from '@/state/server';
import { Button } from '@/components/elements/button/index';
import { PowerAction } from '@/components/server/ServerConsole';

const PowerControls = () => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const instance = ServerContext.useStoreState(state => state.socket.instance);

    const sendPowerCommand = (command: PowerAction) => {
        instance && instance.send('set state', command);
    };

    return (
        <div css={tw`shadow-md bg-neutral-900 rounded-t p-3 flex text-xs justify-center`}>
            <Can action={'control.start'}>
                <Button.Success
                    size={Button.Sizes.Small}
                    css={tw`mr-2`}
                    disabled={status !== 'offline'}
                    onClick={e => {
                        e.preventDefault();
                        sendPowerCommand('start');
                    }}
                >
                    Start
                </Button.Success>
            </Can>
            <Can action={'control.restart'}>
                <Button.Warn
                    size={Button.Sizes.Small}
                    css={tw`mr-2`}
                    disabled={!status}
                    onClick={e => {
                        e.preventDefault();
                        sendPowerCommand('restart');
                    }}
                >
                    Restart
                </Button.Warn>
            </Can>
            <Can action={'control.stop'}>
                <Button.Danger
                    size={Button.Sizes.Small}
                    css={tw`mr-2`}
                    disabled={!status || status === 'offline'}
                    onClick={e => {
                        e.preventDefault();
                        sendPowerCommand('stop');
                    }}
                >
                    Stop
                </Button.Danger>
            </Can>
            <Can action={'control.stop'}>
                <Button.Danger
                    size={Button.Sizes.Small}
                    css={tw`mr-2`}
                    disabled={!status || status === 'offline'}
                    onClick={e => {
                        e.preventDefault();
                        sendPowerCommand('kill');
                    }}
                >
                    Kill
                </Button.Danger>
            </Can>
        </div>
    );
};

export default PowerControls;
