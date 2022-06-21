import stripAnsi from 'strip-ansi';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import consoleShare from '@/api/server/consoleShare';
import { SocketEvent } from '@/components/server/events';
import { Button } from '@/components/elements/button/index';
import FlashMessageRender from '../FlashMessageRender';

export default () => {
    const [ log, setLog ] = useState<string[]>([]);

    const { addFlash, clearFlashes } = useFlash();
    const status = ServerContext.useStoreState(state => state.status.value);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    const addLog = (data: string) => {
        setLog(prevLog => [ ...prevLog, data.startsWith('>') ? data.substring(1) : data ]);
    };

    const submit = () => {
        clearFlashes('console:share');

        const data = stripAnsi(log.map(it => it.replace('\r', '')).join('\n')) || '';

        consoleShare(uuid, data)
            .then(response => {
                addFlash({
                    key: 'console:share',
                    type: 'success',
                    message: 'Your server logs have been posted to: ' + response,
                });
            })
            .catch(error => {
                addFlash({ key: 'console:share', type: 'error', message: httpErrorToHuman(error) });
            });
    };

    useEffect(() => {
        if (!connected || !instance) return;

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, addLog);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, addLog);
        };
    }, [ connected, instance ]);

    return (
        <>
            <FlashMessageRender byKey={'console:share'} className={'mb-2'} />
            <div className={'shadow-md bg-neutral-900 rounded-t p-3 mt-4 flex text-xs justify-center'}>
                <Can action={'console.share'}>
                    <Button
                        size={Button.Sizes.Small}
                        variant={Button.Variants.Secondary}
                        disabled={status !== 'running'}
                        onClick={e => {
                            e.preventDefault();
                            submit();
                        }}
                    >
                        Share console logs
                    </Button>
                </Can>
            </div>
        </>
    );
};
