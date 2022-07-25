import useSWR from 'swr';
import tw from 'twin.macro';
import getServers from '@/api/getServers';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { PaginatedResult } from '@/api/http';
import { useLocation } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import Switch from '@/components/elements/Switch';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import ServerRow from '@/components/dashboard/ServerRow';
import Pagination from '@/components/elements/Pagination';
import { usePersistedState } from '@/plugins/usePersistedState';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ScreenBlock from '@/components/elements/ScreenBlock';
import NotFoundSvg from '@/assets/images/not_found.svg';

export default () => {
    const { search } = useLocation();
    const defaultPage = Number(new URLSearchParams(search).get('page') || '1');

    const [page, setPage] = useState(!isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const uuid = useStoreState((state) => state.user.data!.uuid);
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
    const [showOnlyAdmin, setShowOnlyAdmin] = usePersistedState(`${uuid}:show_all_servers`, false);

    const { data: servers, error } = useSWR<PaginatedResult<Server>>(
        ['/api/client/servers', showOnlyAdmin && rootAdmin, page],
        () => getServers({ page, type: showOnlyAdmin && rootAdmin ? 'admin' : undefined })
    );

    useEffect(() => {
        if (!servers) return;
        if (servers.pagination.currentPage > 1 && !servers.items.length) {
            setPage(1);
        }
    }, [servers?.pagination.currentPage]);

    useEffect(() => {
        // Don't use react-router to handle changing this part of the URL, otherwise it
        // triggers a needless re-render. We just want to track this in the URL incase the
        // user refreshes the page.
        window.history.replaceState(null, document.title, `/${page <= 1 ? '' : `?page=${page}`}`);
    }, [page]);

    useEffect(() => {
        if (error) clearAndAddHttpError({ key: 'dashboard', error });
        if (!error) clearFlashes('dashboard');
    }, [error]);

    return (
        <PageContentBlock title={'Dashboard'} css={tw`mt-4 sm:mt-10`} showFlashKey={'dashboard' || 'store:create'}>
            {rootAdmin && (
                <div css={tw`mb-10 flex justify-between items-center`}>
                    <div>
                        <h1 className={'j-left text-5xl'}>Your Servers</h1>
                        <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>
                            Select a server to view, update or modify.
                        </h3>
                    </div>
                    <Switch
                        name={'show_all_servers'}
                        defaultChecked={showOnlyAdmin}
                        onChange={() => setShowOnlyAdmin((s) => !s)}
                    />
                </div>
            )}
            {!servers ? (
                <Spinner centered size={'large'} />
            ) : (
                <Pagination data={servers} onPageSelect={setPage}>
                    {({ items }) =>
                        items.length > 0 ? (
                            <div className={'lg:grid lg:grid-cols-3 gap-4'}>
                                <>
                                    {items.map((server) => (
                                        <ServerRow
                                            key={server.uuid}
                                            server={server}
                                            className={'j-up'}
                                            css={tw`mt-2`}
                                        />
                                    ))}
                                </>
                            </div>
                        ) : (
                            <ScreenBlock
                                title={'Seems quite quiet here...'}
                                message={'There are no available servers to display.'}
                                image={NotFoundSvg}
                                noContainer
                            />
                        )
                    }
                </Pagination>
            )}
        </PageContentBlock>
    );
};
