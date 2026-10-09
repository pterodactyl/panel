import React from 'react';
import Icon from '@/components/elements/Icon';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/cn';
import CopyOnClick from '@/components/elements/CopyOnClick';

interface StatBlockProps {
    title: string;
    copyOnClick?: string;
    tone?: StatBlockTone;
    icon: LucideIcon;
    children: React.ReactNode;
    className?: string;
}

export type StatBlockTone = 'warning' | 'destructive';

const statBlockClass =
    'flex items-center rounded-sm shadow-lg relative col-span-3 md:col-span-2 lg:col-span-6 px-3 py-2 md:p-3 lg:p-4';

const statusBarClass = 'w-1 h-full absolute left-0 top-0 rounded-l-sm sm:hidden';

const iconClass =
    'hidden shrink-0 items-center justify-center rounded-lg shadow-md w-12 h-12 transition-colors duration-500 sm:flex sm:mr-4 [&>svg]:w-6 [&>svg]:h-6 [&>svg]:m-auto';

const toneClasses = {
    warning: {
        background: 'bg-warning',
        foreground: 'text-warning-foreground',
    },
    destructive: {
        background: 'bg-destructive',
        foreground: 'text-destructive-foreground',
    },
} satisfies Record<StatBlockTone, { background: string; foreground: string }>;

export default function StatBlock({ title, copyOnClick, icon, tone, className, children }: StatBlockProps) {
    const colors = tone
        ? toneClasses[tone]
        : {
              background: 'bg-card',
              foreground: 'text-foreground',
          };

    return (
        <CopyOnClick text={copyOnClick}>
            <div className={cn(statBlockClass, 'bg-popover', className)}>
                <div className={cn(statusBarClass, colors.background)} />
                <div className={cn(iconClass, colors.background)}>
                    <Icon icon={icon} className={colors.foreground} />
                </div>
                <div className='flex flex-col justify-center overflow-hidden w-full'>
                    <p className='font-header font-medium leading-tight text-xs md:text-sm text-foreground'>{title}</p>
                    <div className='h-7 w-full truncate text-lg font-semibold leading-7 text-foreground md:text-xl'>
                        {children}
                    </div>
                </div>
            </div>
        </CopyOnClick>
    );
}
