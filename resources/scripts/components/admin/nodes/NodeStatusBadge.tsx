import { cn } from '@/lib/cn';

export type NodeStatus = 'checking' | 'online' | 'offline';

const statusStyles = {
    checking: {
        label: 'Checking',
        dot: 'animate-pulse bg-muted-foreground',
        badge: 'border-border bg-muted text-muted-foreground',
    },
    online: {
        label: 'Online',
        dot: 'bg-success',
        badge: 'border-success/25 bg-success/10 text-success',
    },
    offline: {
        label: 'Offline',
        dot: 'bg-destructive',
        badge: 'border-destructive/25 bg-destructive/10 text-destructive',
    },
} satisfies Record<NodeStatus, { badge: string; dot: string; label: string }>;

export const NodeStatusBadge = ({ status, title }: { status: NodeStatus; title: string }) => {
    const style = statusStyles[status];

    return (
        <span
            role='status'
            aria-live='polite'
            title={title}
            className={cn(
                'inline-flex items-center gap-1.5 rounded-sm border px-2 py-0.5 text-xs font-medium',
                style.badge
            )}
        >
            <span aria-hidden='true' className={cn('h-1.5 w-1.5 rounded-full', style.dot)} />
            {style.label}
        </span>
    );
};
