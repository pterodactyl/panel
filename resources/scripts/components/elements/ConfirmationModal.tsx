import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';

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
        <h3 className={'mb-6'}>{title}</h3>
        <p className={'text-sm'}>{children}</p>
        <div className={'flex items-center justify-end mt-8'}>
            <button className={'btn btn-secondary btn-sm'} onClick={() => onDismissed()}>
                Cancel
            </button>
            <button className={'btn btn-red btn-sm ml-4'} onClick={() => onConfirmed()}>
                {buttonText}
            </button>
        </div>
    </Modal>
);

export default ConfirmationModal;
