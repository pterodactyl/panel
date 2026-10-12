import { Badge, Card, ProgressBar, bytesToString } from '@pterodactyl/sdk';
import type { ServerStatMetric, UiTone, ViewProps } from '@pterodactyl/sdk/theme';

const tones: Record<NonNullable<ServerStatMetric['alarm']>, UiTone> = { warning: 'warning', critical: 'danger' };

/** A limit of 0 is unlimited, which has no percentage to show. */
const percent = ({ value, limit }: ServerStatMetric) => (limit > 0 ? Math.min((value / limit) * 100, 100) : null);

function Meter({ label, metric, format }: { label: string; metric: ServerStatMetric; format: (value: number) => string }) {
    return (
        <div className='space-y-1.5'>
            <div className='flex justify-between text-xs'>
                <span className='text-muted-foreground'>{label}</span>
                <span>
                    {format(metric.value)}
                    {metric.limit > 0 && <span className='text-muted-foreground'> / {format(metric.limit)}</span>}
                </span>
            </div>
            <ProgressBar
                value={percent(metric)}
                label={label}
                tone={metric.alarm ? tones[metric.alarm] : 'accent'}
            />
        </div>
    );
}

/** Live values from the server's websocket; they update several times a second while it runs. */
export default function Stats({ model }: ViewProps<'server.stats'>) {
    return (
        <Card title='Resources' actions={<Badge tone={model.state === 'running' ? 'success' : 'neutral'}>{model.state ?? 'unknown'}</Badge>}>
            <div className='space-y-4'>
                <Meter label='CPU' metric={model.cpu} format={(value) => `${value.toFixed(1)}%`} />
                <Meter label='Memory' metric={model.memory} format={bytesToString} />
                <Meter label='Disk' metric={model.disk} format={bytesToString} />
                <p className='font-mono text-xs text-muted-foreground'>{model.address ?? 'No allocation'}</p>
            </div>
        </Card>
    );
}
