import { exportEgg } from '@/api/admin/egg';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
// import { jsonLanguage } from '@codemirror/lang-json';
// import Editor from '@/components/elements/Editor';
import { useEffect, useState } from 'react';
import Button from '@/components/elements/Button';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import { useParams } from 'react-router-dom';
import tw from 'twin.macro';

export default ({ className }: { className?: string }) => {
    const params = useParams<'id'>();
    const { clearAndAddHttpError, clearFlashes } = useFlash();

    const [visible, setVisible] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(true);
    const [_content, setContent] = useState<Record<string, any> | null>(null);

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

                {/*<Editor*/}
                {/*    overrides={tw`h-[32rem] rounded`}*/}
                {/*    initialContent={content !== null ? JSON.stringify(content, null, '\t') : ''}*/}
                {/*    mode={jsonLanguage}*/}
                {/*/>*/}

                <div css={tw`flex flex-wrap justify-end mt-4 sm:mt-6`}>
                    <Button
                        type={'button'}
                        css={tw`w-full sm:w-auto sm:mr-2`}
                        onClick={() => setVisible(false)}
                        isSecondary
                    >
                        Close
                    </Button>
                    <Button
                        css={tw`w-full sm:w-auto mt-4 sm:mt-0`}
                        // onClick={submit}
                        // TODO: When clicked, save as a JSON file.
                    >
                        Save
                    </Button>
                </div>
            </Modal>

            <Button
                type={'button'}
                size={'small'}
                css={tw`px-4 py-0 whitespace-nowrap`}
                className={className}
                onClick={() => setVisible(true)}
                isSecondary
            >
                Export
            </Button>
        </>
    );
};
