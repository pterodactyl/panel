import { useMemo, type ComponentProps } from 'react';
import { createLink } from '@tanstack/react-router';
import type { AccountServer, ClientStatsAttributes, ServerPowerState } from '@/api/account/servers/queries';
import { useServerResourceUsage } from '@/api/account/servers/queries';
import { ip, mbToBytes } from '@/lib/formatters';
import { cn } from '@/lib/cn';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Slot from '@/extensions/Slot';
import ComponentView from '@/extensions/ComponentView';
import type { ServerCardModel, ServerCardState } from '@/extensions/componentTypes';
import { relationshipData } from '@/api/relationships';
import { DefaultServerCard, ServerCardContext, serverCardParts } from './ServerCardView';

const ServerRowLink = createLink((props: ComponentProps<'a'>) => <GreyRowBox as={'a'} {...props} />);

const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

const statusToColor = (status: ServerPowerState | undefined): string =>
    !status || status === 'offline' ? 'bg-destructive' : status === 'running' ? 'bg-success' : 'bg-warning';

type ServerAlarms = { cpu: boolean; memory: boolean; disk: boolean };

type ServerLimits = AccountServer['attributes']['limits'];

const computeAlarms = (statsAttributes: ClientStatsAttributes | undefined, limits: ServerLimits): ServerAlarms => {
    if (!statsAttributes) {
        return { cpu: false, memory: false, disk: false };
    }

    return {
        cpu: limits.cpu === 0 ? false : statsAttributes.resources.cpu_absolute >= limits.cpu * 0.9,
        memory: isAlarmState(statsAttributes.resources.memory_bytes, limits.memory),
        disk: limits.disk === 0 ? false : isAlarmState(statsAttributes.resources.disk_bytes, limits.disk),
    };
};

function resolveState(
    attributes: AccountServer['attributes'],
    stats: ClientStatsAttributes | undefined,
    suspended: boolean | undefined,
    alarms: ServerAlarms
): ServerCardState {
    if (suspended)
        return { kind: 'unavailable', reason: attributes.status === 'suspended' ? 'suspended' : 'connection-error' };
    if (attributes.is_node_under_maintenance) return { kind: 'unavailable', reason: 'maintenance' };
    if (stats)
        return {
            kind: 'ready',
            power: stats.current_state,
            cpu: { value: stats.resources.cpu_absolute, limit: attributes.limits.cpu, alarm: alarms.cpu },
            memory: {
                value: stats.resources.memory_bytes,
                limit: mbToBytes(attributes.limits.memory),
                alarm: alarms.memory,
            },
            disk: { value: stats.resources.disk_bytes, limit: mbToBytes(attributes.limits.disk), alarm: alarms.disk },
        };
    if (attributes.is_transferring) return { kind: 'unavailable', reason: 'transferring' };
    if (attributes.status === 'installing') return { kind: 'unavailable', reason: 'installing' };
    if (attributes.status === 'restoring_backup') return { kind: 'unavailable', reason: 'restoring-backup' };
    return attributes.status ? { kind: 'unavailable', reason: 'unavailable' } : { kind: 'loading' };
}

export default function ServerRow({ server, className }: { server: AccountServer; className?: string }) {
    const { attributes } = server;
    const statsAttributes = useServerResourceUsage(
        attributes.uuid,
        attributes.status !== 'suspended' && !attributes.is_node_under_maintenance
    ).data?.attributes;
    const fallbackSuspended = attributes.status === 'suspended';

    const isSuspended = statsAttributes?.is_suspended || fallbackSuspended;

    const model = useMemo<ServerCardModel>(
        () => ({
            identifier: attributes.identifier,
            uuid: attributes.uuid,
            name: attributes.name,
            description: attributes.description,
            address:
                relationshipData(attributes.relationships?.allocations)
                    .filter((allocation) => allocation.attributes.is_default)
                    .map(
                        ({ attributes: allocation }) => `${allocation.ip_alias || ip(allocation.ip)}:${allocation.port}`
                    )
                    .join('') || null,
            state: resolveState(
                attributes,
                statsAttributes,
                isSuspended,
                computeAlarms(statsAttributes, attributes.limits)
            ),
        }),
        [attributes, statsAttributes, isSuspended]
    );
    const context = useMemo(() => ({ model, server }), [model, server]);

    return (
        <ServerRowLink
            to={'/server/$id'}
            params={{ id: attributes.identifier }}
            aria-label={attributes.name}
            className={cn('group grid grid-cols-12 gap-4 relative', className)}
        >
            <Slot name={'dashboard.serverRow.before'} data={server} />
            <div className='col-span-12'>
                <ServerCardContext.Provider value={context}>
                    <ComponentView
                        name='dashboard.serverCard'
                        resetKey={attributes.uuid}
                        props={{ model, Default: DefaultServerCard, parts: serverCardParts }}
                        loading={
                            <div className='h-12 animate-pulse rounded-sm bg-muted' aria-label='Loading server card' />
                        }
                    />
                </ServerCardContext.Provider>
            </div>
            <Slot name={'dashboard.serverRow.metrics.after'} data={server} />
            <Slot name={'dashboard.serverRow.after'} data={server} />
            <div
                className={cn(
                    'status-bar w-2 absolute right-0 z-20 rounded-full m-1 opacity-50 transition-opacity duration-150',
                    'h-[calc(100%-0.5rem)] group-hover:opacity-75',
                    statusToColor(statsAttributes?.current_state)
                )}
            />
        </ServerRowLink>
    );
}
