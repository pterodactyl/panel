import { Badge, ProgressBar, Spinner, bytesToString } from '@pterodactyl/sdk';
import { Slot, useThemeSettings, type ServerCardMetric, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from '../theme-types';

const percent = ({ value, limit }: ServerCardMetric) => (limit > 0 ? Math.min((value / limit) * 100, 100) : null);

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
                    <div>
                        CPU {state.cpu.value.toFixed(0)}%
                        <ProgressBar value={percent(state.cpu)} label='CPU' tone={state.cpu.alarm ? 'danger' : 'accent'} />
                    </div>
                    <div>
                        {bytesToString(state.memory.value)}
                        <ProgressBar value={percent(state.memory)} label='Memory' tone={state.memory.alarm ? 'danger' : 'accent'} />
                    </div>
                    <div>
                        {bytesToString(state.disk.value)}
                        <ProgressBar value={percent(state.disk)} label='Disk' tone={state.disk.alarm ? 'danger' : 'accent'} />
                    </div>
                </div>
            )}
        </div>
    );
}
