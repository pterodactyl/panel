import { lazy, Suspense, type ReactElement } from 'react';
import Spinner from '@/components/elements/Spinner';

const QRCodeSVG = lazy(() => import('qrcode.react').then(({ QRCodeSVG: Code }) => ({ default: Code })));

export interface QrCodeProps {
    value: string;
    className?: string;
}
/** `value` as a QR code; the renderer loads on first use, with a spinner meanwhile. */
export function QrCode({ value, className }: QrCodeProps): ReactElement {
    return (
        <Suspense fallback={<Spinner />}>
            <QRCodeSVG value={value} className={className} />
        </Suspense>
    );
}
