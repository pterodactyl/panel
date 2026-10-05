import * as React from 'react';
import { cn } from '@/lib/cn';

export default function AccountOverviewCardGrid({ className, ...props }: React.ComponentProps<'div'>) {
    return <div className={cn('flex flex-wrap', className)} {...props} />;
}
