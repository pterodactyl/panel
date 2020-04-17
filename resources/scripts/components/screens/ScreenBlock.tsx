import React from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons/faArrowLeft';
import { faSyncAlt } from '@fortawesome/free-solid-svg-icons/faSyncAlt';
import classNames from 'classnames';
import styled from 'styled-components';

interface BaseProps {
    title: string;
    image: string;
    message: string;
    onRetry?: () => void;
    onBack?: () => void;
}

interface PropsWithRetry extends BaseProps {
    onRetry?: () => void;
    onBack?: never | undefined;
}

interface PropsWithBack extends BaseProps {
    onBack?: () => void;
    onRetry?: never | undefined;
}

type Props = PropsWithBack | PropsWithRetry;

const ActionButton = styled.button`
    ${tw`rounded-full w-8 h-8 flex items-center justify-center`};

    &.hover\\:spin:hover {
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
`;

export default ({ title, image, message, onBack, onRetry }: Props) => (
    <PageContentBlock>
        <div className={'flex justify-center'}>
            <div className={'w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-100 rounded-lg shadow-lg text-center relative'}>
                {(typeof onBack === 'function' || typeof onRetry === 'function') &&
                <div className={'absolute pin-l pin-t ml-4 mt-4'}>
                    <ActionButton
                        onClick={() => onRetry ? onRetry() : (onBack ? onBack() : null)}
                        className={classNames('btn btn-primary', { 'hover:spin': !!onRetry })}
                    >
                        <FontAwesomeIcon icon={onRetry ? faSyncAlt : faArrowLeft}/>
                    </ActionButton>
                </div>
                }
                <img src={image} className={'w-2/3 h-auto select-none'}/>
                <h2 className={'mt-6 text-neutral-900 font-bold'}>{title}</h2>
                <p className={'text-sm text-neutral-700 mt-2'}>
                    {message}
                </p>
            </div>
        </div>
    </PageContentBlock>
);
