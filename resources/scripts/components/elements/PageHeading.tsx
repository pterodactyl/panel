import React from 'react';
import { cn } from '@/lib/cn';
import { pageTitleClass } from '@/components/ui/typography';

interface Props {
    title: React.ReactNode;
    description?: React.ReactNode;
    actions?: React.ReactNode;
    className?: string;
}

export default function PageHeading({ title, description, actions, className }: Props) {
    return (
        <header className={cn('mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between', className)}>
            <div className={'min-w-0'}>
                <h1 className={pageTitleClass}>{title}</h1>
                {description && <p className={'mt-1 text-sm leading-relaxed text-foreground/70'}>{description}</p>}
            </div>
            {actions && <div className={'shrink-0'}>{actions}</div>}
        </header>
    );
}
