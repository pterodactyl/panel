import React from 'react';
import type { SpinnerSize } from '@/components/elements/Spinner';
import Spinner from '@/components/elements/Spinner';
import { cn } from '@/lib/cn';

interface Props {
    visible: boolean;
    fixed?: boolean;
    size?: SpinnerSize;
    // Backdrop opacity from 0 to 1; defaults to the `--spinner-overlay-opacity` theme token.
    backgroundOpacity?: number;
    children?: React.ReactNode;
}

const SpinnerOverlay = ({ size, fixed, visible, backgroundOpacity, children }: Props) => {
    if (!visible) {
        return null;
    }

    return (
        <div
            className={cn(
                'top-0 left-0 flex items-center justify-center w-full h-full rounded-sm flex-col z-40',
                'bg-background/(--spinner-overlay-opacity)',
                fixed ? 'fixed' : 'absolute'
            )}
            style={
                backgroundOpacity === undefined
                    ? undefined
                    : ({ '--spinner-overlay-opacity': `${backgroundOpacity * 100}%` } as React.CSSProperties)
            }
        >
            <Spinner size={size} />
            {children && <p className='mt-4 text-muted-foreground'>{children}</p>}
        </div>
    );
};

export default SpinnerOverlay;
