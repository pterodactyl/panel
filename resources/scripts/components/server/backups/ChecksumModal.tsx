import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';

const ChecksumModal = ({ checksum, ...props }: RequiredModalProps & { checksum: string }) => (
    <Modal {...props}>
        <h3 className={'mb-6'}>Verify file checksum</h3>
        <p className={'text-sm'}>
            The SHA256 checksum of this file is:
        </p>
        <pre className={'mt-2 text-sm p-2 bg-neutral-900 rounded'}>
            <code className={'block font-mono'}>{checksum}</code>
        </pre>
    </Modal>
);

export default ChecksumModal;
