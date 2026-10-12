import React from 'react';
import type { SpinnerSize } from '@/components/elements/Spinner';
import Spinner from '@/components/elements/Spinner';
import { useSkin } from '@/themes/skins';

export interface SpinnerOverlayProps {
    visible: boolean;
    fixed?: boolean;
    size?: SpinnerSize;
    // Backdrop opacity from 0 to 1; defaults to the `--spinner-overlay-opacity` theme token.
    backgroundOpacity?: number;
    children?: React.ReactNode;
}

const SpinnerOverlay = ({ size, fixed, visible, backgroundOpacity, children }: SpinnerOverlayProps) => {
    const ui = useSkin('SpinnerOverlay', { fixed: !!fixed });

    if (!visible) {
        return null;
    }

    return (
        <div
            {...ui('root')}
            style={
                backgroundOpacity === undefined
                    ? undefined
                    : ({ '--spinner-overlay-opacity': `${backgroundOpacity * 100}%` } as React.CSSProperties)
            }
        >
            <Spinner size={size} />
            {children && <p {...ui('text')}>{children}</p>}
        </div>
    );
};

export default SpinnerOverlay;
