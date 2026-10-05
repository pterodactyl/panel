import React from 'react';
import { cn } from '@/lib/cn';

const ContentContainer = ({ className, ...props }: React.ComponentProps<'div'>) => (
    <div className={cn('mx-4 max-w-panel xl:mx-auto', className)} {...props} />
);
ContentContainer.displayName = 'ContentContainer';

export default ContentContainer;
