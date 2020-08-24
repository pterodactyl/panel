import axios from 'axios';
import getFileUploadUrl from '@/api/server/files/getFileUploadUrl';
import useServer from '@/plugins/useServer';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import React, { useEffect, useState } from 'react';
import styled from 'styled-components/macro';
import { ModalMask } from '@/components/elements/Modal';
import Fade from '@/components/elements/Fade';
import useEventListener from '@/plugins/useEventListener';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { ServerContext } from '@/state/server';

const InnerContainer = styled.div`
  max-width: 600px;
  ${tw`bg-black w-full border-4 border-primary-500 border-dashed rounded p-10 mx-10`}
`;

export default () => {
    const { uuid } = useServer();
    const [ visible, setVisible ] = useState(false);
    const [ loading, setLoading ] = useState(false);
    const { mutate } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const directory = ServerContext.useStoreState(state => state.files.directory);

    useEventListener('dragenter', e => {
        e.stopPropagation();
        setVisible(true);
    }, true);

    useEventListener('dragexit', e => {
        e.stopPropagation();
        setVisible(false);
    }, true);

    useEffect(() => {
        if (!visible) return;

        const hide = () => setVisible(false);

        window.addEventListener('keydown', hide);
        return () => {
            window.removeEventListener('keydown', hide);
        };
    }, [ visible ]);

    const onFileDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();

        setVisible(false);
        if (e.dataTransfer === undefined || e.dataTransfer === null) {
            return;
        }

        const form = new FormData();
        Array.from(e.dataTransfer.files).forEach(file => form.append('files', file));

        setLoading(true);
        clearFlashes('files');
        getFileUploadUrl(uuid)
            .then(url => axios.post(`${url}&directory=${directory}`, form, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }))
            .then(() => mutate())
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: 'files' });
            })
            .then(() => setVisible(false))
            .then(() => setLoading(false));
    };

    return (
        <>
            <Fade
                appear
                in={visible}
                timeout={75}
                key={'upload_modal_mask'}
                unmountOnExit
            >
                <ModalMask onClick={() => setVisible(false)} onDrop={onFileDrop} onDragOver={e => e.preventDefault()}>
                    <div css={tw`w-full flex items-center justify-center`} style={{ pointerEvents: 'none' }}>
                        <InnerContainer>
                            <p css={tw`text-lg text-neutral-200 text-center`}>
                                Drag and drop files to upload.
                            </p>
                        </InnerContainer>
                    </div>
                </ModalMask>
            </Fade>
            <SpinnerOverlay visible={loading} size={'large'}/>
            <Button css={tw`mr-2`} onClick={() => setVisible(true)}>
                Upload
            </Button>
        </>
    );
};
