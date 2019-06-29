import React from 'react';
import classNames from 'classnames';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';

export default ({ large, visible }: { visible: boolean; large?: boolean }) => (
    <CSSTransition timeout={150} classNames={'fade'} in={visible} unmountOnExit={true}>
        <div
            className={classNames('absolute pin-t pin-l flex items-center justify-center w-full h-full rounded')}
            style={{ background: 'rgba(0, 0, 0, 0.45)' }}
        >
            <Spinner large={large}/>
        </div>
    </CSSTransition>
);
