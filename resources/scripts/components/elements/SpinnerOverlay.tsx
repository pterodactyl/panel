import type { ReactNode } from 'react';
import tw from 'twin.macro';

import Spinner, { SpinnerSize } from '@/components/elements/Spinner';

interface Props {
    children?: ReactNode;

    visible: boolean;
    fixed?: boolean;
    size?: SpinnerSize;
    backgroundOpacity?: number;
}

function SpinnerOverlay({ size, fixed, visible, backgroundOpacity, children }: Props) {
    if (!visible) {
        return null;
    }

    return (
        <div
            css={[
                tw`top-0 left-0 flex items-center justify-center w-full h-full rounded flex-col z-40`,
                !fixed ? tw`absolute` : tw`fixed`,
            ]}
            style={{ background: `rgba(0, 0, 0, ${backgroundOpacity || 0.45})` }}
        >
            <Spinner size={size} />
            {children && (typeof children === 'string' ? <p css={tw`mt-4 text-neutral-400`}>{children}</p> : children)}
        </div>
    );
}

export default SpinnerOverlay;
