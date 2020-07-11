import React from 'react';
import Spinner, { SpinnerSize } from '@/components/elements/Spinner';
import Fade from '@/components/elements/Fade';
import tw from 'twin.macro';

interface Props {
    visible: boolean;
    fixed?: boolean;
    size?: SpinnerSize;
    backgroundOpacity?: number;
}

const SpinnerOverlay = ({ size, fixed, visible, backgroundOpacity }: Props) => (
    <Fade timeout={150} in={visible} unmountOnExit>
        <div
            css={[
                tw`top-0 left-0 flex items-center justify-center w-full h-full rounded`,
                !fixed ? tw`absolute` : tw`fixed`,
            ]}
            style={{ zIndex: 9999, background: `rgba(0, 0, 0, ${backgroundOpacity || 0.45})` }}
        >
            <Spinner size={size}/>
        </div>
    </Fade>
);

export default SpinnerOverlay;
