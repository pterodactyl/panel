import { Clock, CloudDownload, CloudUpload, Cpu, HardDrive, MemoryStick, Wifi } from 'lucide-react';
import { bytesToString, cn, CopyOnClick, Stat } from '@/sdk';
import type { ServerRunState, ServerStatMetric, UiTone, ViewProps } from '@/sdk/theme';

const tile = 'col-span-3 md:col-span-2 lg:col-span-6';

function uptimeTone(state: ServerRunState | null): UiTone {
    if (state === 'running') {
        return 'neutral';
    }

    return state === 'offline' ? 'danger' : 'warning';
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

const limitText = (limit: string | null) => <>/ {limit || <>&infin;</>}</>;

const bytesLimit = (limit: number) => (limit ? bytesToString(limit) : null);

const tone = (metric: ServerStatMetric): UiTone => metric.alarm ?? 'neutral';

export default function Stats({ model, className }: ViewProps<'server.stats'>) {
    const offline = model.state === 'offline';
    const address = model.address ?? 'n/a';
    const { cpu, memory, disk } = model;

    return (
        <div className={cn('grid grid-cols-6 gap-2 md:gap-4', className)}>
            <CopyOnClick text={address}>
                <Stat icon={Wifi} label='Address' value={address} className={tile} />
            </CopyOnClick>
            <Stat
                icon={Clock}
                label='Uptime'
                value={<Uptime state={model.state} uptime={model.uptime} />}
                tone={uptimeTone(model.state)}
                className={tile}
            />
            <Stat
                icon={Cpu}
                label='CPU Load'
                value={offline ? 'Offline' : `${cpu.value.toFixed(2)}%`}
                limit={offline ? undefined : limitText(cpu.limit ? `${cpu.limit}%` : null)}
                tone={tone(cpu)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={MemoryStick}
                label='Memory'
                value={offline ? 'Offline' : bytesToString(memory.value)}
                limit={offline ? undefined : limitText(bytesLimit(memory.limit))}
                tone={tone(memory)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={HardDrive}
                label='Disk'
                value={bytesToString(disk.value)}
                limit={limitText(bytesLimit(disk.limit))}
                tone={tone(disk)}
                className={tile}
            />
            <Stat
                icon={CloudDownload}
                label='Network (Inbound)'
                value={offline ? 'Offline' : bytesToString(model.network.rx)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={CloudUpload}
                label='Network (Outbound)'
                value={offline ? 'Offline' : bytesToString(model.network.tx)}
                unavailable={offline}
                className={tile}
            />
        </div>
    );
}
