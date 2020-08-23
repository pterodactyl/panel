import React, { useEffect, useMemo, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons';
import Spinner from '@/components/elements/Spinner';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import Fade from '@/components/elements/Fade';

export interface RequiredModalProps {
    visible: boolean;
    onDismissed: () => void;
    appear?: boolean;
    top?: boolean;
}

export interface ModalProps extends RequiredModalProps {
    dismissable?: boolean;
    closeOnEscape?: boolean;
    closeOnBackground?: boolean;
    showSpinnerOverlay?: boolean;
}

export const ModalMask = styled.div`
    ${tw`fixed z-50 overflow-auto flex w-full inset-0`};
    background: rgba(0, 0, 0, 0.70);
`;

const ModalContainer = styled.div<{ alignTop?: boolean }>`
    ${tw`relative flex flex-col w-full m-auto`};
    max-height: calc(100vh - 8rem);
    max-width: 50%;
    // @todo max-w-screen-lg perhaps?
    ${props => props.alignTop && 'margin-top: 10%'};
    
    & > .close-icon {
        ${tw`absolute right-0 p-2 text-white cursor-pointer opacity-50 transition-all duration-150 ease-linear hover:opacity-100`};
        top: -2rem;
        
        &:hover {${tw`transform rotate-90`}}
    }
`;

const Modal: React.FC<ModalProps> = ({ visible, appear, dismissable, showSpinnerOverlay, top = true, closeOnBackground = true, closeOnEscape = true, onDismissed, children }) => {
    const [ render, setRender ] = useState(visible);

    const isDismissable = useMemo(() => {
        return (dismissable || true) && !(showSpinnerOverlay || false);
    }, [ dismissable, showSpinnerOverlay ]);

    const handleEscapeEvent = (e: KeyboardEvent) => {
        if (isDismissable && closeOnEscape && e.key === 'Escape') {
            setRender(false);
        }
    };

    useEffect(() => setRender(visible), [ visible ]);

    useEffect(() => {
        window.addEventListener('keydown', handleEscapeEvent);

        return () => window.removeEventListener('keydown', handleEscapeEvent);
    }, [ render ]);

    return (
        <Fade
            in={render}
            timeout={150}
            appear={appear || true}
            unmountOnExit
            onExited={() => onDismissed()}
        >
            <ModalMask
                onClick={e => {
                    if (isDismissable && closeOnBackground) {
                        e.stopPropagation();
                        if (e.target === e.currentTarget) {
                            setRender(false);
                        }
                    }
                }}
            >
                <ModalContainer alignTop={top}>
                    {isDismissable &&
                    <div className={'close-icon'} onClick={() => setRender(false)}>
                        <FontAwesomeIcon icon={faTimes}/>
                    </div>
                    }
                    {showSpinnerOverlay &&
                    <Fade timeout={150} appear in>
                        <div
                            css={tw`absolute w-full h-full rounded flex items-center justify-center`}
                            style={{ background: 'hsla(211, 10%, 53%, 0.25)' }}
                        >
                            <Spinner/>
                        </div>
                    </Fade>
                    }
                    <div css={tw`bg-neutral-800 p-6 rounded shadow-md overflow-y-scroll transition-all duration-150`}>
                        {children}
                    </div>
                </ModalContainer>
            </ModalMask>
        </Fade>
    );
};

export default Modal;
