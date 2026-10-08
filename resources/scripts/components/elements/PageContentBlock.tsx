import React, { useEffect } from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import { cn } from '@/lib/cn';

export interface PageContentBlockProps {
    title?: string;
    className?: string;
    children?: React.ReactNode;
}

const PageContentBlock = ({ title, className, children }: PageContentBlockProps) => {
    useEffect(() => {
        document.title = title || document.title;
    }, [title]);

    return (
        <div>
            <ContentContainer className={cn('my-4 sm:my-6', className)}>{children}</ContentContainer>
            <ContentContainer className='mb-4'>
                <p className='text-center text-muted-foreground text-xs'>
                    <a
                        rel='noopener nofollow noreferrer'
                        href='https://pterodactyl.io'
                        target='_blank'
                        className='no-underline text-muted-foreground hover:text-muted-foreground'
                    >
                        Pterodactyl&reg;
                    </a>
                    &nbsp;&copy; 2015 - <span suppressHydrationWarning>{new Date().getFullYear()}</span>
                </p>
            </ContentContainer>
        </div>
    );
};

export default PageContentBlock;
