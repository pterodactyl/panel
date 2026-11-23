import { cn } from '@/lib/cn';
import { useTranslation, type Translation } from '@/sdk/localization';

export type ServerStatus = 'active' | 'installing' | 'suspended';

const statusStyles = {
    active: {
        dot: 'bg-success',
        badge: 'border-success/25 bg-success/10 text-success',
    },
    installing: {
        dot: 'bg-warning',
        badge: 'border-warning/25 bg-warning/10 text-warning',
    },
    suspended: {
        dot: 'bg-destructive',
        badge: 'border-destructive/25 bg-destructive/10 text-destructive',
    },
} satisfies Record<ServerStatus, { badge: string; dot: string }>;

const statusLabels = (t: Translation['t']) =>
    ({
        active: t('Active'),
        installing: t('Installing'),
        suspended: t('Suspended'),
    }) satisfies Record<ServerStatus, string>;

export const ServerStatusBadge = ({ status }: { status: ServerStatus }) => {
    const { t } = useTranslation();
    const style = statusStyles[status];

    return (
        <span
            role='status'
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
