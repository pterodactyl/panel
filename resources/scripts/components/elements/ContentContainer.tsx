import React from 'react';
import { cn } from '@/lib/cn';

/** Page content's width and side margins: the `page-width` utility, tuned by `--layout-gutter`. */
const ContentContainer = ({ className, ...props }: React.ComponentProps<'div'>) => (
    <div className={cn('page-width', className)} {...props} />
);

ContentContainer.displayName = 'ContentContainer';

export default ContentContainer;
