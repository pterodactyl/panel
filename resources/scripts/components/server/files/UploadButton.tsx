import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import React, { useState } from 'react';
import styled from 'styled-components/macro';

const ModalMask = styled.div`
    ${tw`fixed z-50 overflow-auto flex w-full inset-0`};
    background: rgba(0, 0, 0, 0.70);
`;

export default () => {
    const [ visible, setVisible ] = useState(false);

    const onDragOver = (e: any) => {
        e.preventDefault();

        //console.log(e);
    };

    const onDragEnter = (e: any) => {
        e.preventDefault();

        //console.log(e);
    };

    const onDragLeave = (e: any) => {
        e.preventDefault();

        //console.log(e);
    };

    const onFileDrop = (e: any) => {
        e.preventDefault();

        const files = e.dataTransfer.files;
        console.log(files);
    };

    return (
        <>
            {
                visible ?
                    <ModalMask>
                        <div css={tw`w-full flex items-center justify-center`} onDragOver={onDragOver} onDragEnter={onDragEnter} onDragLeave={onDragLeave} onDrop={onFileDrop}>
                            <div css={tw`w-full md:w-3/4 lg:w-3/5 xl:w-2/5 flex flex-col items-center border-2 border-dashed border-neutral-400 rounded py-8 px-12 mx-8 md:mx-0`}>
                                <img src={'/assets/svgs/file_upload.svg'} css={tw`h-auto w-full select-none`}/>
                                <p css={tw`text-lg text-neutral-200 font-normal mt-8`}>Drag and drop files to upload</p>
                            </div>
                        </div>
                    </ModalMask>
                    :
                    null
            }

            <Button css={tw`mr-2`} onClick={() => setVisible(true)}>
                Upload
            </Button>
        </>
    );
};
