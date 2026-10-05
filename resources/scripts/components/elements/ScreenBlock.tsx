import React from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { ArrowLeft, RefreshCw } from 'lucide-react';
import { cn } from '@/lib/cn';
import Button from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import NotFoundSvg from '@/assets/images/not_found.svg';
import ServerErrorSvg from '@/assets/images/server_error.svg';

interface BaseProps {
    title: string;
    image: string;
    message: string;
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

const ActionButton = ({ className, ...props }: React.ComponentProps<typeof Button>) => (
    <Button className={cn('rounded-full w-8 h-8 flex items-center justify-center p-0', className)} {...props} />
);

const ScreenBlock = ({ title, image, message, onBack, onRetry }: ScreenBlockProps) => (
    <PageContentBlock>
        <div className={'flex justify-center'}>
            <div className={'w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-card rounded-lg shadow-lg text-center relative'}>
                {(onBack || onRetry) && (
                    <div className={'absolute left-0 top-0 ml-4 mt-4'}>
                        <ActionButton
                            aria-label={onRetry ? 'Retry' : 'Go back'}
                            title={onRetry ? 'Retry' : 'Go back'}
                            onClick={() => (onRetry ? onRetry() : onBack ? onBack() : null)}
                            className={onRetry ? 'hover:animate-[spin_2s_linear_infinite]' : undefined}
                        >
                            <Icon icon={onRetry ? RefreshCw : ArrowLeft} />
                        </ActionButton>
                    </div>
                )}
                <img src={image} alt={''} className={'w-2/3 h-auto select-none mx-auto'} />
                <h2 className={'mt-10 text-foreground font-bold text-4xl'}>{title}</h2>
                <p className={'text-sm text-muted-foreground mt-2'}>{message}</p>
            </div>
        </div>
    </PageContentBlock>
);

type ServerErrorProps = (Omit<PropsWithBack, 'image' | 'title'> | Omit<PropsWithRetry, 'image' | 'title'>) & {
    title?: string;
};

const ServerError = ({ title = 'Something went wrong', message, onBack, onRetry }: ServerErrorProps) =>
    onRetry ? (
        <ScreenBlock title={title} image={ServerErrorSvg} message={message} onRetry={onRetry} />
    ) : (
        <ScreenBlock title={title} image={ServerErrorSvg} message={message} onBack={onBack} />
    );

const AccessDenied = ({ message }: Pick<BaseProps, 'message'>) => (
    <ScreenBlock title={'Access Denied'} image={ServerErrorSvg} message={message} />
);

const NotFound = ({ title, message, onBack }: Partial<Pick<ScreenBlockProps, 'title' | 'message' | 'onBack'>>) => (
    <ScreenBlock
        title={title || '404'}
        image={NotFoundSvg}
        message={message || 'The requested resource was not found.'}
        onBack={onBack}
    />
);

export { AccessDenied, ServerError, NotFound };
export default ScreenBlock;
