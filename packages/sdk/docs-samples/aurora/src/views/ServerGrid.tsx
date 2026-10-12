import { Play, RotateCcw, Server as ServerIcon, Square } from 'lucide-react';
import {
    Button,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    PageHeading,
    PanelLink,
    Spinner,
    Switch,
    useSendServerPower,
    type SdkPowerSignal,
} from '@pterodactyl/sdk';
import { Slot, View, type ServerListItem, type ServerListModel, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Sends a signal through the client API, so it works without a console connection. The panel checks
 * the user's control permissions and shows its own notice when the request fails.
 */
function PowerButtons({ item }: { item: ServerListItem }) {
    const power = useSendServerPower(item.server.attributes.uuid);
    const send = (signal: SdkPowerSignal) => () => void power.mutateAsync({ signal }).catch(() => {});
    const running = item.power !== null && item.power !== 'offline';

    return (
        <div className='flex gap-2'>
            {running ? (
                <>
                    <Button size='xsmall' color='grey' disabled={power.isPending} onClick={send('restart')}>
                        <RotateCcw className='size-3.5' aria-hidden /> Restart
                    </Button>
                    <Button size='xsmall' color='red' disabled={power.isPending} onClick={send('stop')}>
                        <Square className='size-3.5' aria-hidden /> Stop
                    </Button>
                </>
            ) : (
                <Button size='xsmall' color='green' disabled={power.isPending || item.power === null} onClick={send('start')}>
                    <Play className='size-3.5' aria-hidden /> Start
                </Button>
            )}
        </div>
    );
}

function Tile({ item }: { item: ServerListItem }) {
    const { server } = item;

    return (
        <li className='glass flex flex-col gap-4 rounded-2xl p-5'>
            <Slot name='dashboard.serverRow.before' data={server} />
            <PanelLink
                destination={{ to: '/server/$id', params: { id: server.attributes.identifier } }}
                aria-label={server.attributes.name}
                className='text-foreground no-underline'
            >
                <View name='dashboard.serverCard' server={server} />
            </PanelLink>
            <Slot name='dashboard.serverRow.metrics.after' data={server} />
            <PowerButtons item={item} />
            <Slot name='dashboard.serverRow.after' data={server} />
        </li>
    );
}

function Pager({ model }: { model: ServerListModel }) {
    const { pagination, actions } = model;

    if (!pagination || pagination.totalPages < 2) {
        return null;
    }

    return (
        <nav aria-label='Pages' className='mt-6 flex items-center justify-center gap-3 text-sm'>
            <Button size='xsmall' color='grey' disabled={pagination.currentPage === 1} onClick={() => actions.setPage(pagination.currentPage - 1)}>
                Previous
            </Button>
            {pagination.currentPage} of {pagination.totalPages}
            <Button
                size='xsmall'
                color='grey'
                disabled={pagination.currentPage === pagination.totalPages}
                onClick={() => actions.setPage(pagination.currentPage + 1)}
            >
                Next
            </Button>
        </nav>
    );
}

/** The dashboard's servers as a grid of cards, each with power buttons outside its link. */
export default function ServerGrid({ model }: ViewProps<'dashboard.serverList'>) {
    const others = model.scope === 'others';

    return (
        <>
            <PageHeading
                title='Servers'
                actions={
                    model.canViewOthers && (
                        <Switch
                            checked={others}
                            onChange={() => model.actions.setScope(others ? 'mine' : 'others')}
                            label="Show other users' servers"
                            controlPosition='end'
                        />
                    )
                }
            />
            <Slot name='dashboard.before' />
            {model.loading && <Spinner size='large' centered />}
            {!model.loading && model.servers.length === 0 && (
                <Empty className='glass rounded-2xl'>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <ServerIcon />
                        </EmptyMedia>
                        <EmptyTitle>No servers yet</EmptyTitle>
                        <EmptyDescription>Servers you own or are invited to show up here.</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            )}
            <ul className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
                {model.servers.map((item) => (
                    <Tile key={item.server.attributes.uuid} item={item} />
                ))}
            </ul>
            <Pager model={model} />
            <Slot name='dashboard.after' />
        </>
    );
}
