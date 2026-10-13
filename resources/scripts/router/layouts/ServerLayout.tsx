import { useEffect } from 'react';
import { Outlet, useLocation, useParams } from '@tanstack/react-router';
import { useServerQuery } from '@/api/server/queries';
import type { Server } from '@/api/server/types';
import { useServerStore } from '@/state/server';
import { ServerContext } from '@/state/server/context';
import { httpErrorToHuman } from '@/api/http';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import InstallListener from '@/components/server/InstallListener';
import StatsListener from '@/components/server/StatsListener';
import TransferListener from '@/components/server/TransferListener';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { useIsRenderedRoute } from '@/router/renderedRoute';
import { useCurrentUser } from '@/api/account/queries';
import { View } from '@/views/View';

/**
 * The children of `layout.server`: the server's listeners, its console and the current page, or
 * the conflict notice instead of both. The engine owns them, so a theme's layout only places them in <Main>.
 */
function ServerContent({ id, server }: { id: string; server: Server }) {
    const location = useLocation();
    const rootAdmin = useCurrentUser().rootAdmin;
    const { identifier } = server.attributes;
    const inConflictState =
        server.attributes.status !== null ||
        server.attributes.is_transferring ||
        server.attributes.is_node_under_maintenance;

    // The console stays mounted and is shown or hidden here, while every other page comes
    // from <Outlet />. Both have to switch in the same render: following the location
    // showed the console above the page being left, and hid it before the next one arrived.
    const isConsole = useIsRenderedRoute(`/server/${id}`);

    return (
        <>
            <InstallListener />
            <TransferListener />
            <WebsocketHandler />
            <StatsListener />
            {inConflictState && (!rootAdmin || !location.pathname.endsWith(`/server/${identifier}`)) ? (
                <View name='server.conflict' />
            ) : (
                <>
                    {/* Stays mounted while hidden so the terminal survives tab switches. */}
                    <div className={isConsole ? undefined : 'hidden'}>
                        <Spinner.Suspense>
                            <View name='page.server.console' active={isConsole} />
                        </Spinner.Suspense>
                    </div>
                    <Outlet />
                </>
            )}
        </>
    );
}

function ServerArea() {
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const { data: server, error } = useServerQuery(id);
    const clearServerState = useServerStore((state) => state.clearServerState);

    useEffect(() => () => clearServerState(), [clearServerState]);

    if (!server) {
        return error ? <ServerError message={httpErrorToHuman(error)} /> : <Spinner size='large' centered />;
    }

    return (
        <View name='layout.server'>
            <ServerContent id={id} server={server} />
        </View>
    );
}

export default function ServerLayout() {
    const { id } = useParams({ from: '/authenticated/server/$id' });

    return (
        <ServerContext.Provider key={id}>
            <ServerArea />
        </ServerContext.Provider>
    );
}
