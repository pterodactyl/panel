import React from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faSyncAlt } from '@fortawesome/free-solid-svg-icons';
import styled, { keyframes } from 'styled-components/macro';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

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

const spin = keyframes`
    to { transform: rotate(360deg) }
`;

const ActionButton = styled(Button)`
    ${tw`rounded-full w-8 h-8 flex items-center justify-center p-0`};

    &.hover\\:spin:hover {
        animation: ${spin} 2s linear infinite;
    }
`;

export default ({ title, image, message, onBack, onRetry }: Props) => (
    <PageContentBlock>
        <div css={tw`flex justify-center`}>
            <div css={tw`w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-100 rounded-lg shadow-lg text-center relative`}>
                {(typeof onBack === 'function' || typeof onRetry === 'function') &&
                <div css={tw`absolute left-0 top-0 ml-4 mt-4`}>
                    <ActionButton
                        onClick={() => onRetry ? onRetry() : (onBack ? onBack() : null)}
                        className={onRetry ? 'hover:spin' : undefined}
                    >
                        <FontAwesomeIcon icon={onRetry ? faSyncAlt : faArrowLeft}/>
                    </ActionButton>
                </div>
                }
                <img src={image} css={tw`w-2/3 h-auto select-none mx-auto`}/>
                <h2 css={tw`mt-10 text-neutral-900 font-bold text-4xl`}>{title}</h2>
                <p css={tw`text-sm text-neutral-700 mt-2`}>
                    {message}
                </p>
            </div>
        </div>
    </PageContentBlock>
);
