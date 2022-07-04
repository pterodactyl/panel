import tw from 'twin.macro';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import React, { useEffect, useState } from 'react';
import { Button } from '@/components/elements/button/index';
import FlashMessageRender from '@/components/FlashMessageRender';
import saveFileContents from '@/api/server/files/saveFileContents';
import { SocketEvent, SocketRequest } from '@/components/server/events';

const EulaModalFeature = () => {
    const [visible, setVisible] = useState(false);
    const [loading, setLoading] = useState(false);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const status = ServerContext.useStoreState((state) => state.status.value);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { connected, instance } = ServerContext.useStoreState((state) => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const listener = (line: string) => {
            if (line.toLowerCase().indexOf('you need to agree to the eula in order to run the server') >= 0) {
                setVisible(true);
            }
        };

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, listener);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, listener);
        };
    }, [connected, instance, status]);

    const onAcceptEULA = () => {
        setLoading(true);
        clearFlashes('feature:eula');

        saveFileContents(uuid, 'eula.txt', 'eula=true')
            .then(() => {
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                setLoading(false);
                setVisible(false);
            })
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'feature:eula', error });
            })
            .then(() => setLoading(false));
    };

    useEffect(() => {
        clearFlashes('feature:eula');
    }, []);

    return (
        <Modal
            visible={visible}
            onDismissed={() => setVisible(false)}
            closeOnBackground={false}
            showSpinnerOverlay={loading}
        >
            <FlashMessageRender key={'feature:eula'} css={tw`mb-4`} />
            <h2 css={tw`text-2xl mb-4 text-neutral-100`}>Accept Minecraft&reg; EULA</h2>
            <p css={tw`text-neutral-200`}>
                By pressing {'"I Accept"'} below you are indicating your agreement to the&nbsp;
                <a
                    target={'_blank'}
                    css={tw`text-primary-300 underline transition-colors duration-150 hover:text-primary-400`}
                    rel={'noreferrer noopener'}
                    href='https://account.mojang.com/documents/minecraft_eula'
                >
                    Minecraft&reg; EULA
                </a>
                .
            </p>
            <div css={tw`mt-8 sm:flex items-center justify-end`}>
                <Button
                    variant={Button.Variants.Secondary}
                    onClick={() => setVisible(false)}
                    css={tw`w-full sm:w-auto border-transparent`}
                >
                    Cancel
                </Button>
                <Button onClick={onAcceptEULA} css={tw`mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto`}>
                    I Accept
                </Button>
            </div>
        </Modal>
    );
};

export default EulaModalFeature;
