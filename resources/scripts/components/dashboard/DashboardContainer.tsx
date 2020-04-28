import React, { useEffect, useState } from 'react';
import ReactGA from 'react-ga';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import { useStoreState } from 'easy-peasy';
import { usePersistedState } from '@/plugins/usePersistedState';
import Switch from '@/components/elements/Switch';

export default () => {
    const { addError, clearFlashes } = useFlash();
    const [ servers, setServers ] = useState<Server[]>([]);
    const [ loading, setLoading ] = useState(true);
    const { rootAdmin } = useStoreState(state => state.user.data!);
    const [ showAdmin, setShowAdmin ] = usePersistedState('show_all_servers', false);

    const loadServers = () => {
        clearFlashes();
        setLoading(true);

        getServers(undefined, showAdmin)
            .then(data => setServers(data.items))
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    useEffect(() => {
        loadServers();
    }, [ showAdmin ]);

    useEffect(() => {
        ReactGA.pageview(location.pathname)
    }, []);

    return (
        <PageContentBlock>
            <FlashMessageRender className={'mb-4'}/>
            {rootAdmin &&
            <div className={'mb-2 flex justify-end items-center'}>
                <p className={'uppercase text-xs text-neutral-400 mr-2'}>
                    {showAdmin ? 'Showing all servers' : 'Showing your servers'}
                </p>
                <Switch
                    name={'show_all_servers'}
                    defaultChecked={showAdmin}
                    onChange={() => setShowAdmin(s => !s)}
                />
            </div>
            }
            {loading ?
                <Spinner centered={true} size={'large'}/>
                :
                servers.length > 0 ?
                    servers.map(server => (
                        <ServerRow key={server.uuid} server={server} className={'mt-2'}/>
                    ))
                    :
                    <p className={'text-center text-sm text-neutral-400'}>
                        There are no servers associated with your account.
                    </p>
            }
        </PageContentBlock>
    );
};