import React, { useContext } from 'react';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { withTranslation, WithTranslation } from 'react-i18next';

interface Props {
    apiKey: string;
}

const ApiKeyModal = ({ t, apiKey }: Props & WithTranslation) => {
    const { dismiss } = useContext(ModalContext);

    return (
        <>
            <h3 css={tw`mb-6 text-2xl`}>{t('account:api.new_title')}</h3>
            <p css={tw`text-sm mb-6`}>
                {t('account:api.new_desc')}
            </p>
            <pre css={tw`text-sm bg-neutral-900 rounded py-2 px-4 font-mono`}>
                <CopyOnClick text={apiKey}><code css={tw`font-mono`}>{apiKey}</code></CopyOnClick>
            </pre>
            <div css={tw`flex justify-end mt-6`}>
                <Button type={'button'} onClick={dismiss}>
                    {t('elements:close')}
                </Button>
            </div>
        </>
    );
};

ApiKeyModal.displayName = 'ApiKeyModal';

export default asModal<Props>({
    closeOnEscape: false,
    closeOnBackground: false,
})(withTranslation([ 'elements', 'account' ])(ApiKeyModal));
