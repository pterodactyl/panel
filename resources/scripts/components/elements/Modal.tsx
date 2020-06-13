import React, { useEffect, useMemo, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons/faTimes';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import classNames from 'classnames';

export interface RequiredModalProps {
    visible: boolean;
    onDismissed: () => void;
    appear?: boolean;
    top?: boolean;
}

type Props = RequiredModalProps & {
    dismissable?: boolean;
    closeOnEscape?: boolean;
    closeOnBackground?: boolean;
    showSpinnerOverlay?: boolean;
    children: React.ReactNode;
}

export default ({ visible, appear, dismissable, showSpinnerOverlay, top = true, closeOnBackground = true, closeOnEscape = true, onDismissed, children  }: Props) => {
    const [render, setRender] = useState(visible);

    const isDismissable = useMemo(() => {
        return (dismissable || true) && !(showSpinnerOverlay || false);
    }, [dismissable, showSpinnerOverlay]);

    const handleEscapeEvent = (e: KeyboardEvent) => {
        if (isDismissable && closeOnEscape && e.key === 'Escape') {
            setRender(false);
        }
    };

    useEffect(() => {
        setRender(visible);
    }, [visible]);

    useEffect(() => {
        window.addEventListener('keydown', handleEscapeEvent);

        return () => window.removeEventListener('keydown', handleEscapeEvent);
    }, [render]);

    return (
        <CSSTransition
            timeout={250}
            classNames={'fade'}
            appear={appear}
            in={render}
            unmountOnExit={true}
            onExited={() => onDismissed()}
        >
            <div className={'modal-mask'} onClick={e => {
                if (isDismissable && closeOnBackground) {
                    e.stopPropagation();
                    if (e.target === e.currentTarget) {
                        setRender(false);
                    }
                }
            }}>
                <div className={classNames('modal-container w-4/5 md:w-full md:max-w-1/2', { top })}>
                    {isDismissable &&
                    <div className={'modal-close-icon'} onClick={() => setRender(false)}>
                        <FontAwesomeIcon icon={faTimes}/>
                    </div>
                    }
                    {showSpinnerOverlay &&
                    <div
                        className={'absolute w-full h-full rounded flex items-center justify-center'}
                        style={{ background: 'hsla(211, 10%, 53%, 0.25)' }}
                    >
                        <Spinner/>
                    </div>
                    }
                    <div className={'modal-content p-6'}>
                        {children}
                    </div>
                </div>
            </div>
        </CSSTransition>
    );
};
