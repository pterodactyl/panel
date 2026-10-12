import { Server as ServerIcon } from 'lucide-react';
import {
    cn,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    ListRow,
    NewLinkButton,
    PageHeading,
    Pagination,
    Spinner,
    Switch,
} from '@/sdk';
import { Slot, View, type ServerListItem, type ServerListModel, type UiTone, type ViewProps } from '@/sdk/theme';

/** Running is good, starting and stopping are on their way, and anything else is down. */
function status({ state }: ServerListItem): UiTone {
    if (state.kind !== 'ready' || state.power === 'offline') {
        return 'danger';
    }

    return state.power === 'running' ? 'success' : 'warning';
}

function ServerRow({ item, className }: { item: ServerListItem; className?: string }) {
    const { server } = item;

    return (
        <ListRow
            destination={{ to: '/server/$id', params: { id: item.identifier } }}
            aria-label={item.name}
            status={status(item)}
            className={cn('grid grid-cols-12 gap-4', className)}
        >
            <Slot name='dashboard.serverRow.before' data={server} />
            <div className='col-span-12'>
                <View name='dashboard.serverCard' server={server} />
            </div>
            <Slot name='dashboard.serverRow.metrics.after' data={server} />
            <Slot name='dashboard.serverRow.after' data={server} />
        </ListRow>
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

export default function ServerList({ model, className }: ViewProps<'dashboard.serverList'>) {
    const others = model.scope === 'others';
    const { pagination } = model;

    return (
        <div className={className}>
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
                                <ServerRow key={item.uuid} item={item} className={index > 0 ? 'mt-2' : undefined} />
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
        </div>
    );
}
