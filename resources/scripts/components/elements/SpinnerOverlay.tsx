import React from 'react';
import Spinner, { SpinnerSize } from '@/components/elements/Spinner';
import Fade from '@/components/elements/Fade';
import classNames from 'classnames';

interface Props {
    visible: boolean;
    fixed?: boolean;
    size?: SpinnerSize;
    backgroundOpacity?: number;
}

const SpinnerOverlay: React.FC<Props> = ({ size, fixed, visible, backgroundOpacity, children }) => (
    <Fade timeout={150} in={visible} unmountOnExit>
        <div
            className={classNames('top-0 left-0 flex items-center justify-center w-full h-full rounded flex-col z-40', !fixed ? 'absolute' : 'fixed')}
            style={{ background: `rgba(0, 0, 0, ${backgroundOpacity || 0.45})` }}
        >
            <Spinner size={size} />
            {children && (typeof children === 'string' ? <p className="mt-4 text-neutral-400">{children}</p> : children)}
        </div>
    </Fade>
);

export default SpinnerOverlay;
