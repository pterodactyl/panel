import { Dialog, type DialogProps } from '@/components/elements/dialog';
import ApiKeyModalContent from '@/components/dashboard/ApiKeyModalContent';

interface Props extends DialogProps {
    apiKey: string;
}

export default function ApiKeyModal({ apiKey, onClose, open }: Props) {
    return (
        <Dialog open={open} onClose={onClose} preventExternalClose title={'Your API key'}>
            <ApiKeyModalContent apiKey={apiKey} onClose={onClose} />
        </Dialog>
    );
}
