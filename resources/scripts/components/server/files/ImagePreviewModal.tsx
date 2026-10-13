import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';

interface Props {
    open: boolean;
    name: string;
    src: string | null;
    onClose: () => void;
}

export default function ImagePreviewModal({ open, name, src, onClose }: Props) {
    return (
        <Dialog open={open} title={name} onClose={onClose}>
            <div className='flex min-h-48 items-center justify-center rounded-sm bg-background p-4'>
                {src ? (
                    <img src={src} alt={name} className='max-h-[70vh] max-w-full object-contain' />
                ) : (
                    <Spinner size='large' centered />
                )}
            </div>
        </Dialog>
    );
}
