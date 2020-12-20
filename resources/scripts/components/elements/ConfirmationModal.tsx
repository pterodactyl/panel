import React, { useContext } from 'react';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';
import { useTranslation } from 'react-i18next';

type Props = {
    title: string;
    buttonText: string;
    children: string;
    onConfirmed: () => void;
    showSpinnerOverlay?: boolean;
};

const ConfirmationModal = ({ title, children, buttonText, onConfirmed }: Props) => {
    const { dismiss } = useContext(ModalContext);
    const { t } = useTranslation('elements');

    return (
        <>
            <h2 css={tw`text-2xl mb-6`}>{title}</h2>
            <p css={tw`text-sm`}>{children}</p>
            <div css={tw`flex flex-wrap items-center justify-end mt-8`}>
                <Button isSecondary onClick={() => dismiss()} css={tw`w-full sm:w-auto border-transparent`}>
                    {t('cancel')}
                </Button>
                <Button color={'red'} css={tw`w-full sm:w-auto mt-4 sm:mt-0 sm:ml-4`} onClick={() => onConfirmed()}>
                    {buttonText}
                </Button>
            </div>
        </>
    );
};

ConfirmationModal.displayName = 'ConfirmationModal';

export default asModal<Props>(props => ({
    showSpinnerOverlay: props.showSpinnerOverlay,
}))(ConfirmationModal);
