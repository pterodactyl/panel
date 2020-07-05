import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

type Props = {
    title: string;
    buttonText: string;
    children: string;
    onConfirmed: () => void;
    showSpinnerOverlay?: boolean;
} & RequiredModalProps;

const ConfirmationModal = ({ title, appear, children, visible, buttonText, onConfirmed, showSpinnerOverlay, onDismissed }: Props) => (
    <Modal
        appear={appear || true}
        visible={visible}
        showSpinnerOverlay={showSpinnerOverlay}
        onDismissed={() => onDismissed()}
    >
        <h2 css={tw`text-2xl mb-6`}>{title}</h2>
        <p css={tw`text-sm`}>{children}</p>
        <div css={tw`flex items-center justify-end mt-8`}>
            <Button isSecondary onClick={() => onDismissed()}>
                Cancel
            </Button>
            <Button color={'red'} css={tw`ml-4`} onClick={() => onConfirmed()}>
                {buttonText}
            </Button>
        </div>
    </Modal>
);

export default ConfirmationModal;
