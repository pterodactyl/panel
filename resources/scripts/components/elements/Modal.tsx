import type { ReactNode } from 'react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import styled, { css } from 'styled-components';
import tw from 'twin.macro';

import Spinner from '@/components/elements/Spinner';
import { breakpoint } from '@/theme';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

export interface RequiredModalProps {
    children?: ReactNode;

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
    background: rgba(0, 0, 0, 0.7);
`;

const ModalContainer = styled.div<{ alignTop?: boolean }>`
    max-width: 95%;
    max-height: calc(100vh - 8rem);
    ${breakpoint('md')`max-width: 75%`};
    ${breakpoint('lg')`max-width: 50%`};

    ${tw`relative flex flex-col w-full m-auto`};
    ${props =>
        props.alignTop &&
        css`
            margin-top: 20%;
            ${breakpoint('md')`margin-top: 10%`};
        `};

    margin-bottom: auto;

    & > .close-icon {
        ${tw`absolute right-0 p-2 text-white cursor-pointer opacity-50 transition-all duration-150 ease-linear hover:opacity-100`};
        top: -2.5rem;

        &:hover {
            ${tw`transform rotate-90`}
        }

        & > svg {
            ${tw`w-6 h-6`};
        }
    }
`;

function Modal({
    visible,
    appear,
    dismissable,
    showSpinnerOverlay,
    top = true,
    closeOnBackground = true,
    closeOnEscape = true,
    onDismissed,
    children,
}: ModalProps) {
    const [render, setRender] = useState(visible);

    const isDismissable = useMemo(() => {
        return (dismissable || true) && !(showSpinnerOverlay || false);
    }, [dismissable, showSpinnerOverlay]);

    useEffect(() => {
        if (!isDismissable || !closeOnEscape) return;

        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setRender(false);
        };

        window.addEventListener('keydown', handler);
        return () => {
            window.removeEventListener('keydown', handler);
        };
    }, [isDismissable, closeOnEscape, render]);

    useEffect(() => {
        setRender(visible);

        if (!visible) {
            onDismissed();
        }
    }, [visible]);

    return (
        <FadeTransition as={Fragment} show={render} duration="duration-150" appear={appear ?? true} unmount>
            <ModalMask
                onClick={e => e.stopPropagation()}
                onContextMenu={e => e.stopPropagation()}
                onMouseDown={e => {
                    if (isDismissable && closeOnBackground) {
                        e.stopPropagation();
                        if (e.target === e.currentTarget) {
                            setRender(false);
                        }
                    }
                }}
            >
                <ModalContainer alignTop={top}>
                    {isDismissable && (
                        <div className={'close-icon'} onClick={() => setRender(false)}>
                            <svg
                                xmlns={'http://www.w3.org/2000/svg'}
                                fill={'none'}
                                viewBox={'0 0 24 24'}
                                stroke={'currentColor'}
                            >
                                <path
                                    strokeLinecap={'round'}
                                    strokeLinejoin={'round'}
                                    strokeWidth={'2'}
                                    d={'M6 18L18 6M6 6l12 12'}
                                />
                            </svg>
                        </div>
                    )}

                    <FadeTransition duration="duration-150" show={showSpinnerOverlay ?? false} appear>
                        <div
                            css={tw`absolute w-full h-full rounded flex items-center justify-center`}
                            style={{ background: 'hsla(211, 10%, 53%, 0.35)', zIndex: 9999 }}
                        >
                            <Spinner />
                        </div>
                    </FadeTransition>

                    <div
                        css={tw`bg-neutral-800 p-3 sm:p-4 md:p-6 rounded shadow-md overflow-y-scroll transition-all duration-150`}
                    >
                        {children}
                    </div>
                </ModalContainer>
            </ModalMask>
        </FadeTransition>
    );
}

function PortaledModal({ children, ...props }: ModalProps): JSX.Element {
    const element = useRef(document.getElementById('modal-portal'));

    return createPortal(<Modal {...props}>{children}</Modal>, element.current!);
}

export default PortaledModal;
