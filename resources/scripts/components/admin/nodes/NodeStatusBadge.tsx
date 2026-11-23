import { cn } from '@/lib/cn';
import { useTranslation, type Translation } from '@/sdk/localization';

export type NodeStatus = 'checking' | 'online' | 'offline';

const statusStyles = {
    checking: {
        dot: 'animate-pulse bg-muted-foreground',
        badge: 'border-border bg-muted text-muted-foreground',
    },
    online: {
        dot: 'bg-success',
        badge: 'border-success/25 bg-success/10 text-success',
    },
    offline: {
        dot: 'bg-destructive',
        badge: 'border-destructive/25 bg-destructive/10 text-destructive',
    },
} satisfies Record<NodeStatus, { badge: string; dot: string }>;

const statusLabels = (t: Translation['t']) =>
    ({
        checking: t('Checking'),
        online: t('Online'),
        offline: t('Offline'),
    }) satisfies Record<NodeStatus, string>;

export const NodeStatusBadge = ({ status, title }: { status: NodeStatus; title: string }) => {
    const { t } = useTranslation();
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
            {statusLabels(t)[status]}
        </span>
    );
};
