import { createContext, useContext } from 'react';
import { Cpu, EthernetPort, HardDrive, MemoryStick, Server as ServerIcon } from 'lucide-react';
import Spinner from '@/components/elements/Spinner';
import { ServerRowMetricDescription, ServerRowMetricIcon } from './ServerRowMetrics';
import { bytesToString } from '@/lib/formatters';
import { cn } from '@/lib/cn';
import Slot from '@/extensions/Slot';
import type { AccountServer } from '@/api/account/servers/queries';
import type {
    ComponentPartProps,
    ComponentParts,
    DefaultComponentProps,
    ServerCardModel,
} from '@/extensions/componentTypes';

export const ServerCardContext = createContext<{ model: ServerCardModel; server: AccountServer } | null>(null);
type PartProps = ComponentPartProps<'dashboard.serverCard'>;

function Identity({ model }: PartProps) {
    const context = useContext(ServerCardContext)!;
    return (
        <div className='flex items-center col-span-12 sm:col-span-5 lg:col-span-6'>
            <div className='icon mr-4'>
                <ServerIcon size='1em' className='inline-block' />
            </div>
            <div>
                <p className='text-lg wrap-break-word'>{model.name}</p>
                {!!model.description && (
                    <p className='text-sm text-muted-foreground wrap-break-word line-clamp-2'>{model.description}</p>
                )}
            </div>
            <Slot name='dashboard.serverRow.name.after' data={context.server} />
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
function Metrics({ model }: PartProps) {
    const state = model.state;
    return (
        <div className='hidden col-span-7 lg:col-span-4 sm:flex items-baseline justify-center'>
            {state.kind === 'loading' ? (
                <Spinner size='small' />
            ) : state.kind === 'unavailable' ? (
                <div className='flex-1 text-center'>
                    <span
                        className={cn(
                            'rounded-sm px-2 py-1 text-xs',
                            state.reason === 'maintenance'
                                ? 'bg-warning text-warning-foreground'
                                : ['suspended', 'connection-error'].includes(state.reason)
                                  ? 'bg-destructive text-destructive-foreground'
                                  : 'bg-popover text-foreground'
                        )}
                    >
                        {unavailableLabels[state.reason]}
                    </span>
                </div>
            ) : (
                [
                    {
                        id: 'cpu',
                        icon: Cpu,
                        metric: state.cpu,
                        value: `${state.cpu.value.toFixed(2)} %`,
                        limit: state.cpu.limit === 0 ? 'Unlimited' : `${state.cpu.limit} %`,
                    },
                    {
                        id: 'memory',
                        icon: MemoryStick,
                        metric: state.memory,
                        value: bytesToString(state.memory.value),
                        limit: state.memory.limit === 0 ? 'Unlimited' : bytesToString(state.memory.limit),
                    },
                    {
                        id: 'disk',
                        icon: HardDrive,
                        metric: state.disk,
                        value: bytesToString(state.disk.value),
                        limit: state.disk.limit === 0 ? 'Unlimited' : bytesToString(state.disk.limit),
                    },
                ].map(({ id, icon, metric, value, limit }) => (
                    <div className='flex-1 ml-4 sm:block hidden' key={id}>
                        <div className='flex justify-center'>
                            <ServerRowMetricIcon icon={icon} $alarm={metric.alarm} />
                            <ServerRowMetricDescription $alarm={metric.alarm}>{value}</ServerRowMetricDescription>
                        </div>
                        <p className='text-xs text-muted-foreground text-center mt-1'>of {limit}</p>
                    </div>
                ))
            )}
        </div>
    );
}
export const serverCardParts: ComponentParts<'dashboard.serverCard'> = {
    identity: Identity,
    address: Address,
    metrics: Metrics,
};

export function DefaultServerCard({ className, parts }: DefaultComponentProps<'dashboard.serverCard'>) {
    const { model } = useContext(ServerCardContext)!;
    const IdentityPart = parts?.identity ?? Identity;
    const AddressPart = parts?.address ?? Address;
    const MetricsPart = parts?.metrics ?? Metrics;
    return (
        <div className={cn('grid grid-cols-12 gap-4', className)}>
            <IdentityPart model={model} />
            <AddressPart model={model} />
            <MetricsPart model={model} />
        </div>
    );
}
