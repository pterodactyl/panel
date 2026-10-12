import React, { useEffect } from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import PageFooter from '@/components/elements/PageFooter';
import { setPageTitle } from '@/views/pageTitle';

export interface PageContentBlockProps {
    /** Titles the document. */
    title?: string;
    /** Classes for the block's outermost element, such as geometry tokens: `[--layout-gutter:0px]`. */
    className?: string;
    children?: React.ReactNode;
}

/**
 * A page's content and its footer. The content is `page-width` with `--layout-page-spacing` above and
 * below; the geometry tokens cascade, so a page changes its own by setting them in `className`.
 */
const PageContentBlock = ({ title, className, children }: PageContentBlockProps) => {
    useEffect(() => {
        if (title) {
            setPageTitle(title);
        }
    }, [title]);

    return (
        <div className={className}>
            <ContentContainer className='my-(--layout-page-spacing)'>{children}</ContentContainer>
            <PageFooter />
        </div>
    );
};

export default PageContentBlock;
