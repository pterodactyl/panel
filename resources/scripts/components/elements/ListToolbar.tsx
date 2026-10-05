import type * as React from 'react';
import { cn } from '@/lib/cn';

interface Props {
    /** Usage or status text shown on the left. */
    summary?: React.ReactNode;
    /** Primary actions, right-aligned. */
    children?: React.ReactNode;
    className?: string;
}

/** The row above a list holding its summary and primary actions. */
export default function ListToolbar({ summary, children, className }: Props) {
    return (
        <div className={cn('mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between', className)}>
            {summary && <p className={'text-sm text-muted-foreground'}>{summary}</p>}
            {children && <div className={'flex flex-col gap-2 sm:ml-auto sm:flex-row sm:items-center'}>{children}</div>}
        </div>
    );
}
