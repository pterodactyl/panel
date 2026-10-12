import { Server as ServerIcon } from 'lucide-react';
import {
    cn,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    NewLinkButton,
    PageHeading,
    Pagination,
    PanelLink,
    Spinner,
    Switch,
} from '@/sdk';
import { Slot, View, type ServerListItem, type ServerListModel, type ViewProps } from '@/sdk/theme';

const rowClass = [
    'flex rounded-sm no-underline text-foreground items-center bg-card p-4 border border-transparent overflow-hidden',
    '[&_.icon]:flex [&_.icon]:w-16 [&_.icon]:items-center [&_.icon]:justify-center [&_.icon]:rounded-full [&_.icon]:bg-popover [&_.icon]:p-3',
    'transition-[background-color,border-color,color,box-shadow] duration-150 hover:bg-popover hover:border-border active:bg-popover/80',
    'group grid grid-cols-12 gap-4 relative',
];

function statusColor(power: ServerListItem['power']): string {
    if (!power || power === 'offline') {
        return 'bg-destructive';
    }

    return power === 'running' ? 'bg-success' : 'bg-warning';
}

function ServerRow({ item, className }: { item: ServerListItem; className?: string }) {
    const { server } = item;

    return (
        <PanelLink
            destination={{ to: '/server/$id', params: { id: server.attributes.identifier } }}
            aria-label={server.attributes.name}
            className={cn(...rowClass, className)}
        >
            <Slot name='dashboard.serverRow.before' data={server} />
            <div className='col-span-12'>
                <View name='dashboard.serverCard' server={server} />
            </div>
            <Slot name='dashboard.serverRow.metrics.after' data={server} />
            <Slot name='dashboard.serverRow.after' data={server} />
            <div
                className={cn(
                    'status-bar w-2 absolute right-0 z-20 rounded-full m-1 opacity-50 transition-opacity duration-150',
                    'h-[calc(100%-0.5rem)] group-hover:opacity-75',
                    statusColor(item.power)
                )}
            />
        </PanelLink>
    );
}

function NoServers({ model }: { model: ServerListModel }) {
    const others = model.scope === 'others';

    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <ServerIcon />
                </EmptyMedia>
                <EmptyTitle>{others ? 'No other servers' : 'No servers yet'}</EmptyTitle>
                <EmptyDescription>
                    {others
                        ? 'There are no servers owned by other users.'
                        : 'Servers you own or are invited to will appear here.'}
                </EmptyDescription>
            </EmptyHeader>
            {model.canCreateServers && (
                <EmptyContent>
                    <NewLinkButton to='/panel/servers/new'>New server</NewLinkButton>
                </EmptyContent>
            )}
        </Empty>
    );
}

export default function ServerList({ model }: ViewProps<'dashboard.serverList'>) {
    const others = model.scope === 'others';
    const { pagination } = model;

    return (
        <>
            <PageHeading
                title='Servers'
                className='mb-4'
                actions={
                    model.canViewOthers && (
                        <Switch
                            checked={others}
                            onChange={() => model.actions.setScope(others ? 'mine' : 'others')}
                            label={others ? "Showing others' servers" : 'Showing your servers'}
                            controlPosition='end'
                            className='gap-2'
                        />
                    )
                }
            />
            <Slot name='dashboard.before' />
            {pagination ? (
                <Pagination
                    data={{
                        data: [...model.servers],
                        meta: {
                            pagination: {
                                total: pagination.total,
                                count: pagination.count,
                                per_page: pagination.perPage,
                                current_page: pagination.currentPage,
                                total_pages: pagination.totalPages,
                            },
                        },
                    }}
                    onPageSelect={model.actions.setPage}
                >
                    {({ items }) =>
                        items.length > 0 ? (
                            items.map((item, index) => (
                                <ServerRow
                                    key={item.server.attributes.uuid}
                                    item={item}
                                    className={index > 0 ? 'mt-2' : undefined}
                                />
                            ))
                        ) : (
                            <NoServers model={model} />
                        )
                    }
                </Pagination>
            ) : (
                <Spinner centered size='large' />
            )}
            <Slot name='dashboard.after' />
        </>
    );
}
