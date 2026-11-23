import { Clock, CloudDownload, CloudUpload, Cpu, HardDrive, MemoryStick, Wifi } from 'lucide-react';
import { bytesToString, cn, CopyOnClick, percentageToString, Stat, useTranslation, type Translation } from '@/sdk';
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
    const { t } = useTranslation();
    const days = Math.floor(seconds / (24 * 60 * 60));
    const hours = Math.floor((Math.floor(seconds) / 60 / 60) % 24);
    const remainder = Math.floor(seconds - hours * 60 * 60);
    const minutes = Math.floor((remainder / 60) % 60);

    if (days > 0) {
        return t('{{days}}d {{hours}}h {{minutes}}m', { days, hours, minutes });
    }

    return t('{{hours}}h {{minutes}}m {{seconds}}s', { hours, minutes, seconds: remainder % 60 });
}

const stateLabels = (t: Translation['t']) =>
    ({
        offline: t('Offline'),
        starting: t('Starting'),
        running: t('Running'),
        stopping: t('Stopping'),
    }) satisfies Record<ServerRunState, string>;

function Uptime({ state, uptime }: { state: ServerRunState | null; uptime: number }) {
    const { t } = useTranslation();

    if (state === null) {
        return t('Offline');
    }

    if (uptime > 0) {
        return <UptimeDuration seconds={uptime / 1000} />;
    }

    return stateLabels(t)[state];
}

const limitText = (limit: string | null) => <>/ {limit || <>∞</>}</>;

const bytesLimit = (limit: number) => (limit ? bytesToString(limit) : null);

const tone = (metric: ServerStatMetric): UiTone => metric.alarm ?? 'neutral';

export default function Stats({ model, className }: ViewProps<'server.stats'>) {
    const { t } = useTranslation();
    const offline = model.state === 'offline';
    const address = model.address ?? t('n/a');
    const { cpu, memory, disk } = model;

    return (
        <div className={cn('grid grid-cols-6 gap-2 md:gap-4', className)}>
            <CopyOnClick text={address}>
                <Stat icon={Wifi} label={t('Address')} value={address} className={tile} />
            </CopyOnClick>
            <Stat
                icon={Clock}
                label={t('Uptime')}
                value={<Uptime state={model.state} uptime={model.uptime} />}
                tone={uptimeTone(model.state)}
                className={tile}
            />
            <Stat
                icon={Cpu}
                label={t('CPU Load')}
                value={offline ? t('Offline') : percentageToString(cpu.value)}
                limit={offline ? undefined : limitText(cpu.limit ? `${cpu.limit}%` : null)}
                tone={tone(cpu)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={MemoryStick}
                label={t('Memory')}
                value={offline ? t('Offline') : bytesToString(memory.value)}
                limit={offline ? undefined : limitText(bytesLimit(memory.limit))}
                tone={tone(memory)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={HardDrive}
                label={t('Disk')}
                value={bytesToString(disk.value)}
                limit={limitText(bytesLimit(disk.limit))}
                tone={tone(disk)}
                className={tile}
            />
            <Stat
                icon={CloudDownload}
                label={t('Network (Inbound)')}
                value={offline ? t('Offline') : bytesToString(model.network.rx)}
                unavailable={offline}
                className={tile}
            />
            <Stat
                icon={CloudUpload}
                label={t('Network (Outbound)')}
                value={offline ? t('Offline') : bytesToString(model.network.tx)}
                unavailable={offline}
                className={tile}
            />
        </div>
    );
}
