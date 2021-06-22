import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import setSelectedDockerImage from '@/api/server/setSelectedDockerImage';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { SocketEvent, SocketRequest } from '@/components/server/events';

const JavaVersionModalFeature = () => {
    const [ visible, setVisible ] = useState(false);
    const [ loading, setLoading ] = useState(false);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const dockerImage = ServerContext.useStoreState(state => state.server.data!.dockerImage);
    const status = ServerContext.useStoreState(state => state.status.value);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const listener = (line: string) => {
            if (line.toLowerCase().indexOf('minecraft 1.17 requires running the server with java 16 or above') >= 0) {
                // paper
                setVisible(true);
            } else if (line.toLowerCase().indexOf('bad major version') >= 0) {
                // vanilla
                setVisible(true);
            } else if (line.toLowerCase().indexOf('unsupported major.minor version') >= 0) {
                // forge
                setVisible(true);
            }
        };

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, listener);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, listener);
        };
    }, [ connected, instance, status ]);

    const updateJava = () => {
        setLoading(true);
        clearFlashes('feature:javaversion');

        setSelectedDockerImage(uuid, 'ghcr.io/pterodactyl/yolks:java_16')
            .then(() => {
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                setLoading(false);
                setVisible(false);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'feature:javaversion', error });
            })
            .then(() => setLoading(false));
    };

    useEffect(() => () => {
        clearFlashes('feature:javaversion');
    }, []);

    return (
        !visible ?
            null
            :
            <Modal visible onDismissed={() => setVisible(false)} closeOnBackground={false} showSpinnerOverlay={loading}>
                <FlashMessageRender key={'feature:javaversion'} css={tw`mb-4`}/>
                <h2 css={tw`text-2xl mb-4 text-neutral-100`}>Invalid Java Version, Update Docker Image?</h2>
                <p>This server is unable to start due to the required java version not being met.</p>
                <br/>
                <p>By pressing {'"Update Docker Image"'} below you are acknowledging that the docker image this server uses will be changed to the default Java 16 image, that is provided by Pterodactyl.</p>
                <br/><br/>
                <p>Current Image: </p><code>{dockerImage}</code>
                <br/>
                <p>New Image: </p><code>ghcr.io/pterodactyl/yolks:java_16</code>
                <div css={tw`mt-8 sm:flex items-center justify-end`}>
                    <Button isSecondary onClick={() => setVisible(false)} css={tw`w-full sm:w-auto border-transparent`}>
                        Cancel
                    </Button>
                    <Button onClick={updateJava} css={tw`mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto`}>
                        Update Docker Image
                    </Button>
                </div>
            </Modal>
    );
};

export default JavaVersionModalFeature;
