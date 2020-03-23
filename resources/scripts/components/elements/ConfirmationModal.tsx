import React from 'react';
import Modal from '@/components/elements/Modal';

interface Props {
    title: string;
    buttonText: string;
    children: string;
    visible: boolean;
    onConfirmed: () => void;
    onCanceled: () => void;
}

const ConfirmationModal = ({ title, children, visible, buttonText, onConfirmed, onCanceled }: Props) => (
    <Modal
        appear={true}
        visible={visible}
        onDismissed={() => onCanceled()}
    >
        <h3 className={'mb-6'}>{title}</h3>
        <p className={'text-sm'}>{children}</p>
        <div className={'flex items-center justify-end mt-8'}>
            <button className={'btn btn-secondary btn-sm'} onClick={() => onCanceled()}>
                Cancel
            </button>
            <button className={'btn btn-red btn-sm ml-4'} onClick={() => onConfirmed()}>
                {buttonText}
            </button>
        </div>
    </Modal>
);

export default ConfirmationModal;
