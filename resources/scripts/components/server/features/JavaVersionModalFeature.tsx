import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import setSelectedDockerImage from '@/api/server/setSelectedDockerImage';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import Select from '@/components/elements/Select';

const dockerImageList = [
    { name: 'Java 8', image: 'ghcr.io/pterodactyl/yolks:java_8' },
    { name: 'Java 11', image: 'ghcr.io/pterodactyl/yolks:java_11' },
    { name: 'Java 16', image: 'ghcr.io/pterodactyl/yolks:java_16' },
];

const JavaVersionModalFeature = () => {
    const [ visible, setVisible ] = useState(false);
    const [ loading, setLoading ] = useState(false);
    const [ selectedVersion, setSelectedVersion ] = useState('ghcr.io/pterodactyl/yolks:java_16');

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const status = ServerContext.useStoreState(state => state.status.value);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const errors = [
            'minecraft 1.17 requires running the server with java 16 or above',
            'java.lang.unsupportedclassversionerror',
            'unsupported major.minor version',
            'has been compiled by a more recent version of the java runtime',
        ];

        const listener = (line: string) => {
            if (errors.some(p => line.toLowerCase().includes(p))) {
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
        clearFlashes('feature:javaVersion');

        setSelectedDockerImage(uuid, selectedVersion)
            .then(() => {
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                setLoading(false);
                setVisible(false);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'feature:javaVersion', error });
            })
            .then(() => setLoading(false));
    };

    useEffect(() => {
        clearFlashes('feature:javaVersion');
    }, []);

    return (
        <Modal visible={visible} onDismissed={() => setVisible(false)} closeOnBackground={false} showSpinnerOverlay={loading}>
            <FlashMessageRender key={'feature:javaVersion'} css={tw`mb-4`}/>
            <h2 css={tw`text-2xl mb-4 text-neutral-100`}>Invalid Java Version, Update Docker Image?</h2>
            <p css={tw`mt-4`}>This server is unable to start due to the required java version not being met.</p>
            <p css={tw`mt-4`}>By pressing {'"Update Docker Image"'} below you are acknowledging that the docker image this server uses will be changed to a image below that has the Java version you are requesting.</p>
            <div css={tw`sm:flex items-center mt-4`}>
                <p>Please select a Java version from the list below.</p>
                <Select
                    onChange={e => setSelectedVersion(e.target.value)}
                >
                    {dockerImageList.map((key, index) => {
                        return (
                            <option key={index} value={key.image}>{key.name}</option>
                        );
                    })}
                </Select>
            </div>
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
