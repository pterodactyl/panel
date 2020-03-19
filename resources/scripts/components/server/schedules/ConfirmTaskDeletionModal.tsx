import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';

type Props = RequiredModalProps & {
    onConfirmed: () => void;
}

export default ({ onConfirmed, ...props }: Props) => (
    <Modal {...props}>
        <h2>Confirm task deletion</h2>
        <p className={'text-sm mt-4'}>
            Are you sure you want to delete this task? This action cannot be undone.
        </p>
        <div className={'flex items-center justify-end mt-8'}>
            <button className={'btn btn-secondary btn-sm'} onClick={() => props.onDismissed()}>
                Cancel
            </button>
            <button className={'btn btn-red btn-sm ml-4'} onClick={() => {
                props.onDismissed();
                onConfirmed();
            }}>
                Delete Task
            </button>
        </div>
    </Modal>
);
