import React from 'react';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';

const InputSpinner = ({ visible, children }: { visible: boolean, children: React.ReactNode }) => (
    <div className={'relative'}>
        <CSSTransition
            timeout={250}
            in={visible}
            unmountOnExit={true}
            appear={true}
            classNames={'fade'}
        >
            <div className={'absolute right-0 h-full flex items-center justify-end pr-3'}>
                <Spinner size={'tiny'}/>
            </div>
        </CSSTransition>
        {children}
    </div>
);

export default InputSpinner;
