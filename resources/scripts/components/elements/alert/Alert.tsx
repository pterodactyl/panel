import { CheckCircle2, Info, ShieldAlert, TriangleAlert, type LucideIcon } from 'lucide-react';
import React from 'react';
import { cn } from '@/lib/cn';

export type AlertType = 'success' | 'info' | 'warning' | 'danger';

interface AlertProps {
    type: AlertType;
    title?: React.ReactNode;
    className?: string;
    children: React.ReactNode;
}

const alertConfig = {
    success: {
        icon: CheckCircle2,
        className: 'border-success bg-success/15',
        iconClassName: 'text-success',
    },
    info: {
        icon: Info,
        className: 'border-accent bg-accent/10',
        iconClassName: 'text-accent',
    },
    warning: {
        icon: TriangleAlert,
        className: 'border-warning bg-warning/15',
        iconClassName: 'text-warning',
    },
    danger: {
        icon: ShieldAlert,
        className: 'border-destructive bg-destructive/15',
        iconClassName: 'text-destructive',
    },
} satisfies Record<AlertType, { icon: LucideIcon; className: string; iconClassName: string }>;

export default function Alert({ type, title, className, children }: AlertProps) {
    const config = alertConfig[type];
    const AlertIcon = config.icon;

    return (
        <div
            role='alert'
            className={cn(
                'flex items-start rounded-sm border border-l-4 px-4 py-3 text-foreground shadow-sm',
                config.className,
                className
            )}
        >
            <AlertIcon aria-hidden className={cn('mr-3 mt-0.5 h-5 w-5 shrink-0', config.iconClassName)} />
            <div className='min-w-0 flex-1 text-sm leading-relaxed'>
                {title && <p className='mb-1 font-semibold text-foreground'>{title}</p>}
                <div>{children}</div>
            </div>
        </div>
    );
}
