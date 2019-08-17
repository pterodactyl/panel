import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons/faTimes';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';

export interface RequiredModalProps {
    visible: boolean;
    onDismissed: () => void;
}

type Props = RequiredModalProps & {
    dismissable?: boolean;
    closeOnEscape?: boolean;
    closeOnBackground?: boolean;
    showSpinnerOverlay?: boolean;
    children: React.ReactNode;
}

export default (props: Props) => {
    const [render, setRender] = useState(props.visible);

    const handleEscapeEvent = (e: KeyboardEvent) => {
        if (props.dismissable !== false && props.closeOnEscape !== false && e.key === 'Escape') {
            setRender(false);
        }
    };

    useEffect(() => setRender(props.visible), [props.visible]);

    useEffect(() => {
        window.addEventListener('keydown', handleEscapeEvent);

        return () => window.removeEventListener('keydown', handleEscapeEvent);
    }, [render]);

    return (
        <CSSTransition
            timeout={250}
            classNames={'fade'}
            in={render}
            unmountOnExit={true}
            onExited={() => props.onDismissed()}
        >
            <div className={'modal-mask'} onClick={e => {
                if (props.dismissable !== false && props.closeOnBackground !== false) {
                    e.stopPropagation();
                    if (e.target === e.currentTarget) {
                        setRender(false);
                    }
                }
            }}>
                <div className={'modal-container top'}>
                    {props.dismissable !== false &&
                    <div className={'modal-close-icon'} onClick={() => setRender(false)}>
                        <FontAwesomeIcon icon={faTimes}/>
                    </div>
                    }
                    {props.showSpinnerOverlay &&
                    <div
                        className={'absolute w-full h-full rounded flex items-center justify-center'}
                        style={{ background: 'hsla(211, 10%, 53%, 0.25)' }}
                    >
                        <Spinner/>
                    </div>
                    }
                    <div className={'modal-content p-6'}>
                        {props.children}
                    </div>
                </div>
            </div>
        </CSSTransition>
    );
};
