import type { ReactNode } from 'react';
import { Clock, CloudDownload, CloudUpload, Cpu, HardDrive, MemoryStick, Wifi, type LucideIcon } from 'lucide-react';
import { bytesToString, cn, CopyOnClick, Icon } from '@/sdk';
import type { ServerRunState, ServerStatMetric, ViewProps } from '@/sdk/theme';

type StatTone = 'warning' | 'destructive';

const statBlockClass =
    'flex items-center rounded-sm shadow-lg relative col-span-3 md:col-span-2 lg:col-span-6 px-3 py-2 md:p-3 lg:p-4';

const statusBarClass = 'w-1 h-full absolute left-0 top-0 rounded-l-sm sm:hidden';

const iconClass =
    'hidden shrink-0 items-center justify-center rounded-lg shadow-md w-12 h-12 transition-colors duration-500 sm:flex sm:mr-4 [&>svg]:w-6 [&>svg]:h-6 [&>svg]:m-auto';

const toneClasses = {
    warning: { background: 'bg-warning', foreground: 'text-warning-foreground' },
    destructive: { background: 'bg-destructive', foreground: 'text-destructive-foreground' },
} satisfies Record<StatTone, { background: string; foreground: string }>;

interface StatBlockProps {
    title: string;
    copyOnClick?: string;
    tone?: StatTone;
    icon: LucideIcon;
    children: ReactNode;
}

function StatBlock({ title, copyOnClick, icon, tone, children }: StatBlockProps) {
    const colors = tone ? toneClasses[tone] : { background: 'bg-card', foreground: 'text-foreground' };

    return (
        <CopyOnClick text={copyOnClick}>
            <div className={cn(statBlockClass, 'bg-popover')}>
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

const tones = { warning: 'warning', critical: 'destructive' } as const;

const metricTone = ({ alarm }: ServerStatMetric): StatTone | undefined => (alarm ? tones[alarm] : undefined);

function uptimeTone(state: ServerRunState | null): StatTone | undefined {
    if (state === 'running') {
        return undefined;
    }

    return state === 'offline' ? 'destructive' : 'warning';
}

/** "1d 2h 3m", or "2h 3m 4s" under a day. */
function UptimeDuration({ seconds }: { seconds: number }) {
    const days = Math.floor(seconds / (24 * 60 * 60));
    const hours = Math.floor((Math.floor(seconds) / 60 / 60) % 24);
    const remainder = Math.floor(seconds - hours * 60 * 60);
    const minutes = Math.floor((remainder / 60) % 60);

    if (days > 0) {
        return (
            <>
                {days}d {hours}h {minutes}m
            </>
        );
    }

    return (
        <>
            {hours}h {minutes}m {remainder % 60}s
        </>
    );
}

function Uptime({ state, uptime }: { state: ServerRunState | null; uptime: number }) {
    if (state === null) {
        return 'Offline';
    }

    if (uptime > 0) {
        return <UptimeDuration seconds={uptime / 1000} />;
    }

    return state.charAt(0).toUpperCase() + state.slice(1);
}

const Limit = ({ limit, children }: { limit: string | null; children: ReactNode }) => (
    <>
        {children}
        <span className='ml-1 text-xs text-muted-foreground select-none'>/ {limit || <>&infin;</>}</span>
    </>
);

const Offline = () => <span className='text-muted-foreground'>Offline</span>;

const bytesLimit = (limit: number) => (limit ? bytesToString(limit) : null);

export default function Stats({ model, className }: ViewProps<'server.stats'>) {
    const offline = model.state === 'offline';
    const address = model.address ?? 'n/a';

    return (
        <div className={cn('grid grid-cols-6 gap-2 md:gap-4', className)}>
            <StatBlock icon={Wifi} title='Address' copyOnClick={address}>
                {address}
            </StatBlock>
            <StatBlock icon={Clock} title='Uptime' tone={uptimeTone(model.state)}>
                <Uptime state={model.state} uptime={model.uptime} />
            </StatBlock>
            <StatBlock icon={Cpu} title='CPU Load' tone={metricTone(model.cpu)}>
                {offline ? (
                    <Offline />
                ) : (
                    <Limit limit={model.cpu.limit ? `${model.cpu.limit}%` : null}>{model.cpu.value.toFixed(2)}%</Limit>
                )}
            </StatBlock>
            <StatBlock icon={MemoryStick} title='Memory' tone={metricTone(model.memory)}>
                {offline ? (
                    <Offline />
                ) : (
                    <Limit limit={bytesLimit(model.memory.limit)}>{bytesToString(model.memory.value)}</Limit>
                )}
            </StatBlock>
            <StatBlock icon={HardDrive} title='Disk' tone={metricTone(model.disk)}>
                <Limit limit={bytesLimit(model.disk.limit)}>{bytesToString(model.disk.value)}</Limit>
            </StatBlock>
            <StatBlock icon={CloudDownload} title='Network (Inbound)'>
                {offline ? <Offline /> : bytesToString(model.network.rx)}
            </StatBlock>
            <StatBlock icon={CloudUpload} title='Network (Outbound)'>
                {offline ? <Offline /> : bytesToString(model.network.tx)}
            </StatBlock>
        </div>
    );
}
