import axios from 'axios';
import getFileUploadUrl from '@/api/server/files/getFileUploadUrl';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import React, { useEffect, useRef, useState } from 'react';
import styled from 'styled-components/macro';
import { ModalMask } from '@/components/elements/Modal';
import Fade from '@/components/elements/Fade';
import useEventListener from '@/plugins/useEventListener';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { ServerContext } from '@/state/server';
import { WithClassname } from '@/components/types';

const InnerContainer = styled.div`
  max-width: 600px;
  ${tw`bg-black w-full border-4 border-primary-500 border-dashed rounded p-10 mx-10`}
`;

export default ({ className }: WithClassname) => {
    const fileUploadInput = useRef<HTMLInputElement>(null);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
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

    const onFileSubmission = (files: FileList) => {
        const form = new FormData();
        Array.from(files).forEach(file => form.append('files', file));

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
                <ModalMask
                    onClick={() => setVisible(false)}
                    onDragOver={e => e.preventDefault()}
                    onDrop={e => {
                        e.preventDefault();
                        e.stopPropagation();

                        setVisible(false);
                        if (!e.dataTransfer?.files.length) return;

                        onFileSubmission(e.dataTransfer.files);
                    }}
                >
                    <div css={tw`w-full flex items-center justify-center`} style={{ pointerEvents: 'none' }}>
                        <InnerContainer>
                            <p css={tw`text-lg text-neutral-200 text-center`}>
                                Drag and drop files to upload.
                            </p>
                        </InnerContainer>
                    </div>
                </ModalMask>
            </Fade>
            <SpinnerOverlay visible={loading} size={'large'} fixed/>
            <input
                type={'file'}
                ref={fileUploadInput}
                css={tw`hidden`}
                onChange={e => {
                    if (!e.currentTarget.files) return;

                    onFileSubmission(e.currentTarget.files);
                    if (fileUploadInput.current) {
                        fileUploadInput.current.files = null;
                    }
                }}
            />
            <Button
                className={className}
                onClick={() => {
                    fileUploadInput.current
                        ? fileUploadInput.current.click()
                        : setVisible(true);
                }}
            >
                Upload
            </Button>
        </>
    );
};
