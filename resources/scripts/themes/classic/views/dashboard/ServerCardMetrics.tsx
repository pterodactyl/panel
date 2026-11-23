import { Cpu, HardDrive, MemoryStick } from 'lucide-react';
import { Badge, bytesToString, cn, Spinner, Stat, useTranslation, type Translation } from '@/sdk';
import type { ServerCardState, ServerStatMetric, ServerUnavailableReason, UiTone, ViewProps } from '@/sdk/theme';

const unavailableLabels = (t: Translation['t']) =>
    ({
        suspended: t('Suspended'),
        'connection-error': t('Connection Error'),
        maintenance: t('Under Maintenance'),
        transferring: t('Transferring'),
        installing: t('Installing'),
        'restoring-backup': t('Restoring Backup'),
        unavailable: t('Unavailable'),
    }) satisfies Record<ServerUnavailableReason, string>;

const unavailableTones = {
    suspended: 'danger',
    'connection-error': 'danger',
    maintenance: 'warning',
    transferring: 'neutral',
    installing: 'neutral',
    'restoring-backup': 'neutral',
    unavailable: 'neutral',
} satisfies Record<ServerUnavailableReason, UiTone>;

function UnavailableBadge({ reason }: { reason: ServerUnavailableReason }) {
    const { t } = useTranslation();

    return (
        <div className='flex-1 text-center'>
            <Badge tone={unavailableTones[reason]}>{unavailableLabels(t)[reason]}</Badge>
        </div>
    );
}

// The card shows only the danger alarm; a warning draws like no alarm.
const tone = ({ alarm }: ServerStatMetric): UiTone => (alarm === 'danger' ? 'danger' : 'neutral');

function MetricsContent({ state }: { state: ServerCardState }) {
    const { t } = useTranslation();

    if (state.kind === 'loading') {
        return <Spinner size='small' />;
    }

    if (state.kind === 'unavailable') {
        return <UnavailableBadge reason={state.reason} />;
    }

    return (
        <>
            {[
                {
                    label: t('CPU'),
                    icon: Cpu,
                    metric: state.cpu,
                    value: `${state.cpu.value.toFixed(2)} %`,
                    limit: state.cpu.limit === 0 ? t('Unlimited') : `${state.cpu.limit} %`,
                },
                {
                    label: t('Memory'),
                    icon: MemoryStick,
                    metric: state.memory,
                    value: bytesToString(state.memory.value),
                    limit: state.memory.limit === 0 ? t('Unlimited') : bytesToString(state.memory.limit),
                },
                {
                    label: t('Disk'),
                    icon: HardDrive,
                    metric: state.disk,
                    value: bytesToString(state.disk.value),
                    limit: state.disk.limit === 0 ? t('Unlimited') : bytesToString(state.disk.limit),
                },
            ].map(({ label, icon, metric, value, limit }) => (
                <Stat
                    key={label}
                    variant='inline'
                    icon={icon}
                    label={label}
                    value={value}
                    limit={t('of {{limit}}', { limit })}
                    tone={tone(metric)}
                    className='flex-1 ml-4 hidden sm:flex'
                />
            ))}
        </>
    );
}

export default function ServerCardMetrics({ model, className }: ViewProps<'dashboard.serverCard.metrics'>) {
    return (
        <div className={cn('hidden col-span-7 lg:col-span-4 sm:flex items-baseline justify-center', className)}>
            <MetricsContent state={model.state} />
        </div>
    );
}
