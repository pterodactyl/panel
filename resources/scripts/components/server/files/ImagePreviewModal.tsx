import { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';

interface Props {
    open: boolean;
    name: string;
    src: string | null;
    onClose: () => void;
}

export default function ImagePreviewModal({ open, name, src, onClose }: Props) {
    const [previewSrc, setPreviewSrc] = useState<string | null>(src);

    useEffect(() => {
        let objectUrl: string | null = null;
        const controller = new AbortController();

        setPreviewSrc(null);

        if (!src) {
            return () => controller.abort();
        }

        if (!/\.svg(?:$|[?#])/i.test(name)) {
            setPreviewSrc(src);

            return () => controller.abort();
        }

        // SVG files may be served with a generic content type, so provide the
        // browser with an explicit image/svg+xml blob before displaying them.
        fetch(src, { signal: controller.signal })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Unable to load ${name}.`);
                }

                return response.blob();
            })
            .then((blob) => {
                objectUrl = URL.createObjectURL(new Blob([blob], { type: 'image/svg+xml' }));
                setPreviewSrc(objectUrl);
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                setPreviewSrc(src);
            });

        return () => {
            controller.abort();

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [name, src]);

    return (
        <Dialog open={open} title={name} onClose={onClose}>
            <div className='flex min-h-48 items-center justify-center rounded-sm bg-background p-4'>
                {previewSrc ? (
                    <img src={previewSrc} alt={name} className='max-h-[70vh] max-w-full object-contain' />
                ) : (
                    <Spinner size='large' centered />
                )}
            </div>
        </Dialog>
    );
}
