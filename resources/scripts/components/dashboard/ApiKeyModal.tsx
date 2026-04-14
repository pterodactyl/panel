import React, { useContext } from 'react';
import Button from '@/components/elements/Button';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';
import CopyOnClick from '@/components/elements/CopyOnClick';

interface Props {
    apiKey: string;
}

const ApiKeyModal = ({ apiKey }: Props) => {
    const { dismiss } = useContext(ModalContext);

    return (
        <>
            <h3 className="mb-6 text-2xl">Your API Key</h3>
            <p className="text-sm mb-6">
                The API key you have requested is shown below. Please store this in a safe location, it will not be
                shown again.
            </p>
            <pre className="text-sm bg-neutral-900 rounded py-2 px-4 font-mono">
                <CopyOnClick text={apiKey}>
                    <code className="font-mono">{apiKey}</code>
                </CopyOnClick>
            </pre>
            <div className="flex justify-end mt-6">
                <Button type={'button'} onClick={() => dismiss()}>
                    Close
                </Button>
            </div>
        </>
    );
};

ApiKeyModal.displayName = 'ApiKeyModal';

export default asModal<Props>({
    closeOnEscape: false,
    closeOnBackground: false,
})(ApiKeyModal);
