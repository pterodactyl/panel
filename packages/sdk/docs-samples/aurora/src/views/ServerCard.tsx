import { Badge, ProgressBar, Spinner, bytesToString } from '@pterodactyl/sdk';
import { Slot, useThemeSettings, type ServerCardMetric, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from '../theme-types';

/** A limit of 0 is unlimited, which has no percentage to show. */
const percent = ({ value, limit }: ServerCardMetric) => (limit > 0 ? Math.min((value / limit) * 100, 100) : null);

function Usage({ label, text, metric }: { label: string; text: string; metric: ServerCardMetric }) {
    return (
        <div className='space-y-1'>
            {text}
            <ProgressBar value={percent(metric)} label={label} tone={metric.alarm ? 'danger' : 'accent'} />
        </div>
    );
}

/** A server's name and address, with its usage as the panel polls it (every 20 seconds at most). */
export default function ServerCard({ model, server }: ViewProps<'dashboard.serverCard'>) {
    const { liveStats } = useThemeSettings<ThemeSettings>();
    const { state } = model;

    return (
        <div className='space-y-3'>
            <div className='flex items-start gap-2'>
                <div className='min-w-0 flex-1'>
                    <p className='truncate font-semibold'>{model.name}</p>
                    <p className='truncate font-mono text-xs text-muted-foreground'>{model.address ?? 'No address'}</p>
                </div>
                <Slot name='dashboard.serverRow.name.after' data={server} />
                {state.kind === 'ready' && (
                    <Badge tone={state.power === 'running' ? 'success' : 'neutral'} variant='soft'>
                        {state.power}
                    </Badge>
                )}
                {state.kind === 'unavailable' && <Badge tone='warning'>{state.reason.replace('-', ' ')}</Badge>}
            </div>
            {state.kind === 'loading' && <Spinner size='small' />}
            {liveStats && state.kind === 'ready' && (
                <div className='grid grid-cols-3 gap-3 text-xs text-muted-foreground'>
                    <Usage label='CPU' text={`CPU ${state.cpu.value.toFixed(0)}%`} metric={state.cpu} />
                    <Usage label='Memory' text={bytesToString(state.memory.value)} metric={state.memory} />
                    <Usage label='Disk' text={bytesToString(state.disk.value)} metric={state.disk} />
                </div>
            )}
        </div>
    );
}
