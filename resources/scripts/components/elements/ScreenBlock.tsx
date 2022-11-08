import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import NotFoundSvg from '@/assets/images/not_found.svg';
import { Button } from '@/components/elements/button/index';
import styled, { keyframes } from 'styled-components/macro';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import PageContentBlock from '@/components/elements/PageContentBlock';

interface BaseProps {
    title: string;
    image: string;
    message: string;
    noContainer?: boolean;
    onRetry?: () => void;
    onBack?: () => void;
}

interface PropsWithRetry extends BaseProps {
    onRetry?: () => void;
    onBack?: never;
}

interface PropsWithBack extends BaseProps {
    onBack?: () => void;
    onRetry?: never;
}

export type ScreenBlockProps = PropsWithBack | PropsWithRetry;

const spin = keyframes`
    to { transform: rotate(360deg) }
`;

const ActionButton = styled(Button)`
    ${tw`rounded-full w-8 h-8 flex items-center justify-center p-0`};

    &.hover\\:spin:hover {
        animation: ${spin} 2s linear infinite;
    }
`;

const ScreenBlock = ({ title, image, message, onBack, onRetry, noContainer }: ScreenBlockProps) => (
    <>
        {noContainer ? (
            <div css={tw`flex justify-center`}>
                <div
                    css={tw`w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-800 rounded-lg shadow-lg text-center relative`}
                >
                    {(typeof onBack === 'function' || typeof onRetry === 'function') && (
                        <div css={tw`absolute left-0 top-0 ml-4 mt-4`}>
                            <ActionButton
                                onClick={() => (onRetry ? onRetry() : onBack ? onBack() : null)}
                                className={onRetry ? 'hover:spin' : undefined}
                            >
                                {onRetry ? <Icon.RefreshCw /> : <Icon.ChevronLeft />}
                            </ActionButton>
                        </div>
                    )}
                    <img src={image} css={tw`w-2/3 h-auto select-none mx-auto`} />
                    <h2 css={tw`mt-10 font-bold text-4xl`}>{title}</h2>
                    <p css={tw`text-sm text-neutral-400 mt-2`}>{message}</p>
                </div>
            </div>
        ) : (
            <PageContentBlock>
                <div css={tw`flex justify-center`}>
                    <div
                        css={tw`w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-900 rounded-lg shadow-lg text-center relative`}
                    >
                        {(typeof onBack === 'function' || typeof onRetry === 'function') && (
                            <div css={tw`absolute left-0 top-0 ml-4 mt-4`}>
                                <ActionButton
                                    onClick={() => (onRetry ? onRetry() : onBack ? onBack() : null)}
                                    className={onRetry ? 'hover:spin' : undefined}
                                >
                                    {onRetry ? <Icon.RefreshCw /> : <Icon.ChevronLeft />}
                                </ActionButton>
                            </div>
                        )}
                        <img src={image} css={tw`w-2/3 h-auto select-none mx-auto`} />
                        <h2 css={tw`mt-10 font-bold text-4xl`}>{title}</h2>
                        <p css={tw`text-sm text-neutral-400 mt-2`}>{message}</p>
                    </div>
                </div>
            </PageContentBlock>
        )}
    </>
);

type ServerErrorProps = (Omit<PropsWithBack, 'image' | 'title'> | Omit<PropsWithRetry, 'image' | 'title'>) & {
    title?: string;
};

type NotApprovedProps = (Omit<PropsWithBack, 'image' | 'title'> | Omit<PropsWithRetry, 'image' | 'title'>) & {
    title: string;
    message: string;
};

const ServerError = ({ title, ...props }: ServerErrorProps) => (
    <ScreenBlock title={title || 'Something went wrong'} image={ServerErrorSvg} {...props} />
);

const NotApproved = ({ title, message }: NotApprovedProps) => (
    <ScreenBlock title={title} image={NotFoundSvg} message={message} />
);

const NotFound = ({ title, message, onBack }: Partial<Pick<ScreenBlockProps, 'title' | 'message' | 'onBack'>>) => (
    <ScreenBlock
        title={title || '404'}
        image={NotFoundSvg}
        message={message || 'The requested resource was not found.'}
        onBack={onBack}
    />
);

export { ServerError, NotFound, NotApproved };
export default ScreenBlock;
