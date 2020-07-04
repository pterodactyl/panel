import React from 'react';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';
import Fade from '@/components/elements/Fade';
import tw from 'twin.macro';

const InputSpinner = ({ visible, children }: { visible: boolean, children: React.ReactNode }) => (
    <div css={tw`relative`}>
        <Fade
            appear
            unmountOnExit
            in={visible}
            timeout={250}
        >
            <div css={tw`absolute right-0 h-full flex items-center justify-end pr-3`}>
                <Spinner size={'small'}/>
            </div>
        </Fade>
        {children}
    </div>
);

export default InputSpinner;
