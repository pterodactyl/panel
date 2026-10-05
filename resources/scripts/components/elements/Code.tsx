import React from 'react';
import { cn } from '@/lib/cn';

interface CodeProps {
    dark?: boolean | undefined;
    className?: string;
    children: React.ReactNode;
}

export default function Code({ dark, className, children }: CodeProps) {
    return (
        <code
            className={cn('font-mono px-1.5 py-0.5 inline-block rounded-sm', className, {
                'bg-sunken': !dark,
                'bg-muted text-foreground': dark,
            })}
        >
            {children}
        </code>
    );
}
