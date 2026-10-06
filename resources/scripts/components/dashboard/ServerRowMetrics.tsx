import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/cn';

export const ServerRowMetricIcon = ({ $alarm, icon: IconComponent }: { $alarm: boolean; icon: LucideIcon }) => (
    <IconComponent size={'1em'} className={cn('inline-block shrink-0', $alarm ? 'text-destructive' : 'text-muted-foreground')} />
);

export function ServerRowMetricDescription({ $alarm, children }: { $alarm: boolean; children: React.ReactNode }) {
    return <p className={cn('text-sm ml-2', $alarm ? 'text-destructive' : 'text-muted-foreground')}>{children}</p>;
}
