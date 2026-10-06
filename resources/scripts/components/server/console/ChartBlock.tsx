import React from 'react';
import { cn } from '@/lib/cn';

interface ChartBlockProps {
    title: string;
    value: React.ReactNode;
    caption?: React.ReactNode;
    usage?: number | null;
    children: React.ReactNode;
}

const usageTone = (fraction: number) =>
    fraction > 0.9 ? 'bg-destructive' : fraction > 0.8 ? 'bg-warning' : 'bg-chart-1';

export default function ChartBlock({ title, value, caption, usage, children }: ChartBlockProps) {
    const fraction = usage === null || usage === undefined ? null : Math.min(1, Math.max(0, usage));

    return (
        <div className={'flex flex-col rounded-sm bg-popover shadow-lg border-b-4 border-border'}>
            <div className={'px-4 pt-3'}>
                <div className={'flex min-w-0 items-baseline justify-between gap-3'}>
                    <h3
                        className={
                            'shrink-0 font-header text-xs font-medium uppercase tracking-wider text-muted-foreground'
                        }
                    >
                        {title}
                    </h3>
                    {caption && (
                        <span className={'truncate text-xs tabular-nums text-muted-foreground'}>{caption}</span>
                    )}
                </div>
                <div className={'mt-1 truncate text-2xl font-semibold leading-8 tabular-nums text-foreground'}>
                    {value}
                </div>
                <div className={cn('mt-2 h-1 overflow-hidden rounded-full', fraction !== null && 'bg-sunken')}>
                    {fraction !== null && (
                        <div
                            className={cn('h-full rounded-full transition-[width] duration-500', usageTone(fraction))}
                            style={{ width: `${Math.max(fraction * 100, fraction > 0 ? 1 : 0)}%` }}
                        />
                    )}
                </div>
            </div>
            <div className={'pt-3 pb-2'}>{children}</div>
        </div>
    );
}
