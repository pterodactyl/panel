import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { SocketEvent } from '@/components/server/events';

const HytaleOauthRequireFeature = () => {
    const [visible, setVisible] = useState(false);
    const [link, setLink] = useState('');

    const status = ServerContext.useStoreState((state) => state.status.value);
    const { clearFlashes } = useFlash();
    const { connected, instance } = ServerContext.useStoreState((state) => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const listener = (line: string) => {
            if (line.match(/https:\/\/oauth\.accounts\.hytale\.com\/oauth2\/device\/verify\?user_code=(.*)/i)) {
                setLink(line);
                setVisible(true);
            }
        };

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, listener);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, listener);
        };
    }, [connected, instance, status]);

    useEffect(() => {
        clearFlashes('feature:hytaleOauth');
    }, []);

    const handleLogin = () => {
        if (link) {
            window.open(link, '_blank', 'noopener,noreferrer');
            setVisible(false);
            setLink('');
        }
    };

    return (
        <Modal
            visible={visible}
            onDismissed={() => {
                setVisible(false);
                setLink('');
            }}
            closeOnBackground={false}
            showSpinnerOverlay={false}
        >
            <FlashMessageRender key={'feature:hytaleOauth'} css={tw`mb-4`} />
            <h2 css={tw`text-2xl mb-4 text-neutral-100`}>Authentication Required</h2>
            <p css={tw`text-neutral-200`}>
                You need to authenticate with your Hytale account to download or update server files. Please log in to
                continue.
            </p>
            <div css={tw`mt-8 sm:flex items-center justify-end`}>
                <Button isSecondary onClick={() => setVisible(false)} css={tw`w-full sm:w-auto border-transparent`}>
                    Cancel
                </Button>
                <Button onClick={handleLogin} css={tw`mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto`}>
                    Log in
                </Button>
            </div>
        </Modal>
    );
};

export default HytaleOauthRequireFeature;
