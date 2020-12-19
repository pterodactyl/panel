import React, { useEffect, useState } from 'react';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { usePersistedState } from '@/plugins/usePersistedState';
import Switch from '@/components/elements/Switch';
import tw from 'twin.macro';
import useSWR from 'swr';
import { PaginatedResult } from '@/api/http';
import Pagination from '@/components/elements/Pagination';
import { useTranslation } from 'react-i18next';

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ page, setPage ] = useState(1);
    const { rootAdmin } = useStoreState(state => state.user.data!);
    const [ showOnlyAdmin, setShowOnlyAdmin ] = usePersistedState('show_all_servers', false);
    const { t } = useTranslation('dashboard');

    const { data: servers, error } = useSWR<PaginatedResult<Server>>(
        [ '/api/client/servers', showOnlyAdmin, page ],
        () => getServers({ page, type: showOnlyAdmin ? 'admin' : undefined }),
    );

    useEffect(() => {
        if (error) clearAndAddHttpError({ key: 'dashboard', error });
        if (!error) clearFlashes('dashboard');
    }, [ error ]);

    return (
        <PageContentBlock title={t('dashboard')} showFlashKey={'dashboard'}>
            {rootAdmin &&
            <div css={tw`mb-2 flex justify-end items-center`}>
                <p css={tw`uppercase text-xs text-neutral-400 mr-2`}>
                    {showOnlyAdmin ? t('show_others_server') : t('show_own_server')}
                </p>
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
                                <ServerRow
                                    key={server.uuid}
                                    server={server}
                                    css={index > 0 ? tw`mt-2` : undefined}
                                />
                            ))
                            :
                            <p css={tw`text-center text-sm text-neutral-400`}>
                                {showOnlyAdmin ? t('no_others_server') : t('no_own_server')}
                            </p>
                    )}
                </Pagination>
            }
        </PageContentBlock>
    );
};
