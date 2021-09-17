import { jsonLanguage } from '@codemirror/lang-json';
import Editor from '@/components/elements/Editor';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';

export default ({ className }: { className?: string }) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <Modal
                visible={visible}
                onDismissed={() => {
                    setVisible(false);
                }}
            >
                <FlashMessageRender byKey={'egg:import'} css={tw`mb-6`}/>

                <h2 css={tw`mb-6 text-2xl text-neutral-100`}>Import Egg</h2>

                <Editor overrides={tw`h-64 rounded`} initialContent={''} mode={jsonLanguage}/>

                <div css={tw`flex flex-wrap justify-end mt-4 sm:mt-6`}>
                    <Button
                        type={'button'}
                        isSecondary
                        css={tw`w-full sm:w-auto sm:mr-2`}
                        onClick={() => setVisible(false)}
                    >
                        Cancel
                    </Button>
                    <Button css={tw`w-full sm:w-auto mt-4 sm:mt-0`}>
                        Import Egg
                    </Button>
                </div>
            </Modal>

            <Button
                type={'button'}
                size={'large'}
                css={tw`h-10 px-4 py-0 whitespace-nowrap`}
                className={className}
                onClick={() => setVisible(true)}
                isSecondary
            >
                Import
            </Button>
        </>
    );
};
