import { Cpu, EthernetPort, HardDrive, MemoryStick, Server as ServerIcon } from 'lucide-react';
import { Badge, bytesToString, cn, ListRow, Spinner, Stat } from '@/sdk';
import {
    Slot,
    type ServerCardState,
    type ServerStatMetric,
    type ServerUnavailableReason,
    type UiTone,
    type ViewPartProps,
    type ViewParts,
    type ViewProps,
} from '@/sdk/theme';

type PartProps = ViewPartProps<'dashboard.serverCard'>;

function Identity({ model, server }: PartProps) {
    return (
        <div className='flex items-center col-span-12 sm:col-span-5 lg:col-span-6'>
            <ListRow.Icon className='mr-4'>
                <ServerIcon size='1em' className='inline-block' />
            </ListRow.Icon>
            <div>
                <p className='text-lg wrap-break-word'>{model.name}</p>
                {!!model.description && (
                    <p className='text-sm text-muted-foreground wrap-break-word line-clamp-2'>{model.description}</p>
                )}
            </div>
            <Slot name='dashboard.serverRow.name.after' data={server} />
        </div>
    );
}

function Address({ model }: PartProps) {
    return (
        <div className='flex-1 ml-4 lg:block lg:col-span-2 hidden'>
            <div className='flex justify-center'>
                <EthernetPort size='1em' className='inline-block shrink-0 text-muted-foreground' />
                <p className='text-sm text-muted-foreground ml-2'>{model.address}</p>
            </div>
        </div>
    );
}

const unavailableLabels = {
    suspended: 'Suspended',
    'connection-error': 'Connection Error',
    maintenance: 'Under Maintenance',
    transferring: 'Transferring',
    installing: 'Installing',
    'restoring-backup': 'Restoring Backup',
    unavailable: 'Unavailable',
} as const;

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
    return (
        <div className='flex-1 text-center'>
            <Badge tone={unavailableTones[reason]}>{unavailableLabels[reason]}</Badge>
        </div>
    );
}

// The card shows only the danger alarm; a warning draws like no alarm.
const tone = ({ alarm }: ServerStatMetric): UiTone => (alarm === 'danger' ? 'danger' : 'neutral');

function MetricsContent({ state }: { state: ServerCardState }) {
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
                    label: 'CPU',
                    icon: Cpu,
                    metric: state.cpu,
                    value: `${state.cpu.value.toFixed(2)} %`,
                    limit: state.cpu.limit === 0 ? 'Unlimited' : `${state.cpu.limit} %`,
                },
                {
                    label: 'Memory',
                    icon: MemoryStick,
                    metric: state.memory,
                    value: bytesToString(state.memory.value),
                    limit: state.memory.limit === 0 ? 'Unlimited' : bytesToString(state.memory.limit),
                },
                {
                    label: 'Disk',
                    icon: HardDrive,
                    metric: state.disk,
                    value: bytesToString(state.disk.value),
                    limit: state.disk.limit === 0 ? 'Unlimited' : bytesToString(state.disk.limit),
                },
            ].map(({ label, icon, metric, value, limit }) => (
                <Stat
                    key={label}
                    variant='inline'
                    icon={icon}
                    label={label}
                    value={value}
                    limit={`of ${limit}`}
                    tone={tone(metric)}
                    className='flex-1 ml-4 hidden sm:flex'
                />
            ))}
        </>
    );
}

function Metrics({ model }: PartProps) {
    return (
        <div className='hidden col-span-7 lg:col-span-4 sm:flex items-baseline justify-center'>
            <MetricsContent state={model.state} />
        </div>
    );
}

const serverCardParts: ViewParts<'dashboard.serverCard'> = { identity: Identity, address: Address, metrics: Metrics };

export default function ServerCard({ model, server, parts, className }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className={cn('grid grid-cols-12 gap-4', className)}>
            <parts.identity model={model} server={server} />
            <parts.address model={model} server={server} />
            <parts.metrics model={model} server={server} />
        </div>
    );
}

ServerCard.parts = serverCardParts;
