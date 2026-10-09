import { ComponentReplacementSession } from '@/extensions/componentSession';
import { useEffect } from 'react';
import { httpErrorToHuman } from '@/api/http';
import { Server } from 'lucide-react';
import ServerRow from '@/components/dashboard/ServerRow';
import { NewLinkButton } from '@/components/elements/NewButton';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import Switch from '@/components/ui/Switch';
import Pagination from '@/components/elements/Pagination';
import { useNavigate } from '@tanstack/react-router';
import { useAccountServers } from '@/api/account/servers/queries';
import { ServerError } from '@/components/elements/ScreenBlock';
import { useCurrentUser } from '@/api/account/queries';
import { getPageSearch, useDashboardSearch } from '@/router/search';
import Slot from '@/extensions/Slot';
import PageHeading from '@/components/elements/PageHeading';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';

function DashboardContainerContent() {
    const navigate = useNavigate();
    const { page, type } = useDashboardSearch();

    const { rootAdmin } = useCurrentUser();
    const serverType = rootAdmin ? type : undefined;
    const showOnlyAdmin = serverType === 'admin';

    const navigateToPage = (page: number) => {
        void navigate({
            to: '/',
            search: serverType ? { ...getPageSearch(page), type: serverType } : getPageSearch(page),
            replace: true,
            viewTransition: false,
        });
    };

    const { data: servers, error, refetch } = useAccountServers({ page, type: serverType });

    const toggleShowOnlyAdmin = () => {
        void navigate({
            to: '/',
            search: showOnlyAdmin ? {} : { type: 'admin' },
            replace: true,
            viewTransition: false,
        });
    };

    useEffect(() => {
        if (!servers) {
            return;
        }

        if (servers.meta.pagination.current_page > 1 && !servers.data.length) {
            void navigate({
                to: '/',
                search: serverType ? { type: serverType } : {},
                replace: true,
                viewTransition: false,
            });
        }
    }, [navigate, serverType, servers]);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <PageContentBlock title='Dashboard'>
            <PageHeading
                title='Servers'
                className='mb-4'
                actions={
                    rootAdmin && (
                        <Switch
                            checked={showOnlyAdmin}
                            onChange={toggleShowOnlyAdmin}
                            label={showOnlyAdmin ? "Showing others' servers" : 'Showing your servers'}
                            controlPosition='end'
                            className='gap-2'
                        />
                    )
                }
            />
            <Slot name='dashboard.before' />
            {servers ? (
                <Pagination data={servers} onPageSelect={navigateToPage}>
                    {({ items }) =>
                        items.length > 0 ? (
                            items.map((server, index) => (
                                <ServerRow
                                    key={server.attributes.uuid}
                                    server={server}
                                    className={index > 0 ? 'mt-2' : undefined}
                                />
                            ))
                        ) : (
                            <Empty className='border bg-card'>
                                <EmptyHeader>
                                    <EmptyMedia variant='icon'>
                                        <Server />
                                    </EmptyMedia>
                                    <EmptyTitle>{showOnlyAdmin ? 'No other servers' : 'No servers yet'}</EmptyTitle>
                                    <EmptyDescription>
                                        {showOnlyAdmin
                                            ? 'There are no servers owned by other users.'
                                            : 'Servers you own or are invited to will appear here.'}
                                    </EmptyDescription>
                                </EmptyHeader>
                                {rootAdmin && (
                                    <EmptyContent>
                                        <NewLinkButton to='/panel/servers/new'>New server</NewLinkButton>
                                    </EmptyContent>
                                )}
                            </Empty>
                        )
                    }
                </Pagination>
            ) : (
                <Spinner centered size='large' />
            )}
            <Slot name='dashboard.after' />
        </PageContentBlock>
    );
}

export default function DashboardContainer() {
    return (
        <ComponentReplacementSession>
            <DashboardContainerContent />
        </ComponentReplacementSession>
    );
}
