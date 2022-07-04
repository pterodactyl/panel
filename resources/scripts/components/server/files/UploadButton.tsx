import axios from 'axios';
import tw from 'twin.macro';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import Fade from '@/components/elements/Fade';
import { ServerContext } from '@/state/server';
import Portal from '@/components/elements/Portal';
import { WithClassname } from '@/components/types';
import { ModalMask } from '@/components/elements/Modal';
import useEventListener from '@/plugins/useEventListener';
import React, { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/elements/button/index';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import getFileUploadUrl from '@/api/server/files/getFileUploadUrl';

const InnerContainer = styled.div`
    max-width: 600px;
    ${tw`bg-black w-full border-4 border-primary-500 border-dashed rounded p-10 mx-10`};
`;

function isFileOrDirectory(event: DragEvent): boolean {
    if (!event.dataTransfer?.types) {
        return false;
    }

    for (let i = 0; i < event.dataTransfer.types.length; i++) {
        // Check if the item being dragged is not a file.
        if (event.dataTransfer.types[i] !== 'Files') {
            return false;
        }
    }

    return true;
}

export default ({ className }: WithClassname) => {
    const fileUploadInput = useRef<HTMLInputElement>(null);

    const [timeouts, setTimeouts] = useState<NodeJS.Timeout[]>([]);
    const [visible, setVisible] = useState(false);
    const [loading, setLoading] = useState(false);
    const { mutate } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const directory = ServerContext.useStoreState((state) => state.files.directory);
    const appendFileUpload = ServerContext.useStoreActions((actions) => actions.files.appendFileUpload);
    const removeFileUpload = ServerContext.useStoreActions((actions) => actions.files.removeFileUpload);

    useEventListener(
        'dragenter',
        (e) => {
            if (!isFileOrDirectory(e)) {
                return;
            }
            e.stopPropagation();
            setVisible(true);
        },
        true
    );

    useEventListener(
        'dragexit',
        (e) => {
            if (!isFileOrDirectory(e)) {
                return;
            }
            e.stopPropagation();
            setVisible(false);
        },
        true
    );

    useEffect(() => {
        if (!visible) return;

        const hide = () => setVisible(false);

        window.addEventListener('keydown', hide);
        return () => {
            window.removeEventListener('keydown', hide);
        };
    }, [visible]);

    useEffect(() => {
        return () => timeouts.forEach(clearTimeout);
    }, []);

    const onFileSubmission = (files: FileList) => {
        setLoading(true);

        const formData: FormData[] = [];
        Array.from(files).forEach((file) => {
            const form = new FormData();
            form.append('files', file);
            formData.push(form);
        });

        clearFlashes('files');

        Promise.all(
            Array.from(formData).map((f) =>
                getFileUploadUrl(uuid).then((url) =>
                    axios.post(`${url}&directory=${directory}`, f, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (data: ProgressEvent) => {
                            // @ts-expect-error this is expected
                            const name = f.getAll('files')[0].name;

                            appendFileUpload({
                                name: name,
                                loaded: data.loaded,
                                total: data.total,
                            });

                            if (data.loaded === data.total) {
                                const timeout = setTimeout(() => removeFileUpload(name), 5000);
                                setTimeouts((t) => [...t, timeout]);
                            }
                        },
                    })
                )
            )
        )
            .then(() => mutate())
            .then(() => setLoading(false))
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'files', error });
            });
    };

    return (
        <>
            <Portal>
                <Fade appear in={visible} timeout={75} key={'upload_modal_mask'} unmountOnExit>
                    <ModalMask
                        onClick={() => setVisible(false)}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            setVisible(false);
                            if (!e.dataTransfer?.files.length) return;

                            onFileSubmission(e.dataTransfer.files);
                        }}
                    >
                        <div css={tw`w-full flex items-center justify-center`} style={{ pointerEvents: 'none' }}>
                            <InnerContainer>
                                <p css={tw`text-lg text-neutral-200 text-center`}>Drag and drop files to upload.</p>
                            </InnerContainer>
                        </div>
                    </ModalMask>
                </Fade>
                <SpinnerOverlay visible={loading} size={'large'} fixed />
            </Portal>
            <input
                type={'file'}
                ref={fileUploadInput}
                css={tw`hidden`}
                onChange={(e) => {
                    if (!e.currentTarget.files) return;
                    onFileSubmission(e.currentTarget.files);
                    if (fileUploadInput.current) {
                        fileUploadInput.current.files = null;
                    }
                }}
            />
            <Button className={className} onClick={() => fileUploadInput.current && fileUploadInput.current.click()}>
                Upload
            </Button>
        </>
    );
};
