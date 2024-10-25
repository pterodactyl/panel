import { LanguageDescription } from '@codemirror/language';
import { json } from '@codemirror/lang-json';
import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import tw from 'twin.macro';

import { exportEgg } from '@/api/admin/egg';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Button } from '@/components/elements/button';
import { Variant } from '@/components/elements/button/types';
import { Editor } from '@/components/elements/editor';
import Modal from '@/components/elements/Modal';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';

export default ({ className }: { className?: string }) => {
    const params = useParams<'id'>();
    const { clearAndAddHttpError, clearFlashes } = useFlash();

    const [visible, setVisible] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(true);
    const [content, setContent] = useState<string | undefined>(undefined);

    useEffect(() => {
        if (!visible) {
            return;
        }

        clearFlashes('egg:export');
        setLoading(true);

        exportEgg(Number(params.id))
            .then(setContent)
            .catch(error => clearAndAddHttpError({ key: 'egg:export', error }))
            .then(() => setLoading(false));
    }, [visible]);

    return (
        <>
            <Modal
                visible={visible}
                onDismissed={() => {
                    setVisible(false);
                }}
                css={tw`relative`}
            >
                <SpinnerOverlay visible={loading} />
                <h2 css={tw`mb-6 text-2xl text-neutral-100`}>Export Egg</h2>
                <FlashMessageRender byKey={'egg:export'} css={tw`mb-6`} />

                <Editor
                    childClassName={tw`h-[32rem] rounded`}
                    initialContent={content ?? ''}
                    language={LanguageDescription.of({ name: 'json', support: json() })}
                />

                <div css={tw`flex flex-wrap justify-end mt-4 sm:mt-6`}>
                    <Button.Text
                        type="button"
                        variant={Variant.Secondary}
                        css={tw`w-full sm:w-auto sm:mr-2`}
                        onClick={() => setVisible(false)}
                    >
                        Close
                    </Button.Text>

                    <Button
                        css={tw`w-full sm:w-auto mt-4 sm:mt-0`}
                        // onClick={submit}
                        // TODO: When clicked, save as a JSON file.
                    >
                        Save
                    </Button>
                </div>
            </Modal>

            <Button.Text
                type="button"
                css={tw`px-4 py-0 whitespace-nowrap`}
                className={className}
                onClick={() => setVisible(true)}
            >
                Export
            </Button.Text>
        </>
    );
};
