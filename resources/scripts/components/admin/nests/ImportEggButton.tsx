import { LanguageDescription } from '@codemirror/language';
import { json } from '@codemirror/lang-json';
import { useState } from 'react';
import { useParams } from 'react-router-dom';
import tw from 'twin.macro';

import getEggs from '@/api/admin/nests/getEggs';
import importEgg from '@/api/admin/nests/importEgg';
import useFlash from '@/plugins/useFlash';
import { Button } from '@/components/elements/button';
import { Size, Variant } from '@/components/elements/button/types';
import { Editor } from '@/components/elements/editor';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';

export default ({ className }: { className?: string }) => {
    const [visible, setVisible] = useState(false);

    const { clearFlashes } = useFlash();

    const params = useParams<'nestId'>();
    const { mutate } = getEggs(Number(params.nestId));

    let fetchFileContent: (() => Promise<string>) | null = null;

    const submit = async () => {
        clearFlashes('egg:import');

        if (fetchFileContent === null) {
            return;
        }

        const egg = await importEgg(Number(params.nestId), await fetchFileContent());
        await mutate(data => ({ ...data!, items: [...data!.items!, egg] }));
        setVisible(false);
    };

    return (
        <>
            <Modal
                visible={visible}
                onDismissed={() => {
                    setVisible(false);
                }}
            >
                <FlashMessageRender byKey={'egg:import'} css={tw`mb-6`} />

                <h2 css={tw`mb-6 text-2xl text-neutral-100`}>Import Egg</h2>

                <Editor
                    childClassName={tw`h-64 rounded`}
                    initialContent={''}
                    fetchContent={value => {
                        fetchFileContent = value;
                    }}
                    language={LanguageDescription.of({ name: 'json', support: json() })}
                />

                <div css={tw`flex flex-wrap justify-end mt-4 sm:mt-6`}>
                    <Button.Text
                        type="button"
                        variant={Variant.Secondary}
                        css={tw`w-full sm:w-auto sm:mr-2`}
                        onClick={() => setVisible(false)}
                    >
                        Cancel
                    </Button.Text>
                    <Button css={tw`w-full sm:w-auto mt-4 sm:mt-0`} onClick={submit}>
                        Import Egg
                    </Button>
                </div>
            </Modal>

            <Button
                type="button"
                size={Size.Large}
                variant={Variant.Secondary}
                css={tw`h-10 px-4 py-0 whitespace-nowrap`}
                className={className}
                onClick={() => setVisible(true)}
            >
                Import
            </Button>
        </>
    );
};
