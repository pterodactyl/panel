import React from 'react';
import classNames from 'classnames';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';

export default ({ large, fixed, visible }: { visible: boolean; fixed?: boolean; large?: boolean }) => (
    <CSSTransition timeout={150} classNames={'fade'} in={visible} unmountOnExit={true}>
        <div
            className={classNames('z-50 pin-t pin-l flex items-center justify-center w-full h-full rounded', {
                absolute: !fixed,
                fixed: fixed,
            })}
            style={{ background: 'rgba(0, 0, 0, 0.45)' }}
        >
            <Spinner large={large}/>
        </div>
    </CSSTransition>
);
