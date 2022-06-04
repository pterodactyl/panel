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
import Pagination from '@/components/elements/Pagination';
import { usePersistedState } from '@/plugins/usePersistedState';
import EditServerRow from '@/components/store/edit/EditServerRow';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default () => {
    const { search } = useLocation();
    const defaultPage = Number(new URLSearchParams(search).get('page') || '1');

    const [ page, setPage ] = useState((!isNaN(defaultPage) && defaultPage > 0) ? defaultPage : 1);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const uuid = useStoreState(state => state.user.data!.uuid);
    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const [ showOnlyAdmin, setShowOnlyAdmin ] = usePersistedState(`${uuid}:show_all_servers`, false);

    const { data: servers, error } = useSWR<PaginatedResult<Server>>(
        [ '/api/client/servers', (showOnlyAdmin && rootAdmin), page ],
        () => getServers({ page, type: (showOnlyAdmin && rootAdmin) ? 'admin' : undefined }),
    );

    useEffect(() => {
        if (!servers) return;
        if (servers.pagination.currentPage > 1 && !servers.items.length) {
            setPage(1);
        }
    }, [ servers?.pagination.currentPage ]);

    useEffect(() => {
        // Don't use react-router to handle changing this part of the URL, otherwise it
        // triggers a needless re-render. We just want to track this in the URL incase the
        // user refreshes the page.
        window.history.replaceState(null, document.title, `/store/edit/${page <= 1 ? '' : `?page=${page}`}`);
    }, [ page ]);

    useEffect(() => {
        if (error) clearAndAddHttpError({ key: 'store:edit', error });
        if (!error) clearFlashes('store:edit');
    }, [ error ]);

    return (
        <PageContentBlock title={'Edit a server'} showFlashKey={'store:edit'}>
            <h1 css={tw`text-5xl`}>Edit your servers</h1>
            <h3 css={tw`text-2xl mt-2 text-neutral-500`}>Configure limits and options for your instances.</h3>
            {rootAdmin &&
                <div css={tw`mb-2 flex justify-end items-center`}>
                    <Switch
                        name={'show_all_servers'}
                        defaultChecked={showOnlyAdmin}
                        onChange={() => setShowOnlyAdmin(s => !s)}
                    />
                </div>
            }
            {!servers ?
                <Spinner centered size={'large'}/>
                :
                <Pagination data={servers} onPageSelect={setPage}>
                    {({ items }) => (
                        items.length > 0 ?
                            items.map((server, index) => (
                                <EditServerRow
                                    key={server.uuid}
                                    server={server}
                                    css={index > 0 ? tw`mt-2` : undefined}
                                />
                            ))
                            :
                            <p css={tw`text-center text-sm text-neutral-400`}>
                                There are no servers available to edit.
                            </p>
                    )}
                </Pagination>
            }
        </PageContentBlock>
    );
};
