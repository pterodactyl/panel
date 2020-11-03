import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import tw from 'twin.macro';

const EulaModalFeature = () => {
    const [ visible, setVisible ] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const listener = (line: string) => {
            if (line.toLowerCase().indexOf('you need to agree to the eula in order to run the server') >= 0) {
                setVisible(true);
            }
        };

        instance.addListener('console output', listener);

        return () => {
            instance.removeListener('console output', listener);
        };
    }, [ connected, instance, status ]);

    return (
        !visible ?
            null
            :
            <Modal visible onDismissed={() => setVisible(false)}>
                <h2 css={tw`text-3xl mb-4 text-neutral-100`}>EULA Not Accepted</h2>
                <p css={tw`text-neutral-200`}>
                    It looks like you have not yet accepted the Minecraft EULA. In order to start this server you
                    must set eula=true inside the eula.txt file in the File Manager.
                </p>
            </Modal>
    );
};

export default EulaModalFeature;
