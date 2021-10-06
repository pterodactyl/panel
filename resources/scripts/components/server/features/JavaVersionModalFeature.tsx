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
import { useTranslation } from 'react-i18next';

const dockerImageList = [
    { name: 'Java 8', image: 'ghcr.io/pterodactyl/yolks:java_8' },
    { name: 'Java 11', image: 'ghcr.io/pterodactyl/yolks:java_11' },
    { name: 'Java 16', image: 'ghcr.io/pterodactyl/yolks:java_16' },
];

const JavaVersionModalFeature = () => {
    const { t } = useTranslation();
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
            <h2 css={tw`text-2xl mb-4 text-neutral-100`}>{t('Eula Invalid Java')}</h2>
            <p css={tw`mt-4`}>{t('Eula Java Version Error')}</p>
            <p css={tw`mt-4`}>{t('Eula Desc 1')} <strong>{t('Eula Desc Button')}</strong> {t('Eula Desc 2')}</p>
            <div css={tw`sm:flex items-center mt-4`}>
                <p>{t('Eula Select Java')}</p>
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
                        {t('Cancel')}
                </Button>
                <Button onClick={updateJava} css={tw`mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto`}>
                        {t('Eula Update Image Button')}
                </Button>
            </div>
        </Modal>
    );
};

export default JavaVersionModalFeature;
