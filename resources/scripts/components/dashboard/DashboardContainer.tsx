import React, { useEffect, useState } from 'react';
import ReactGA from 'react-ga';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default () => {
    const [ servers, setServers ] = useState<null | Server[]>(null);

    const loadServers = () => getServers().then(data => setServers(data.items));

    useEffect(() => {
        loadServers();
    }, []);

    useEffect(() => {
        ReactGA.pageview(location.pathname)
    }, []);

    if (servers === null) {
        return <Spinner size={'large'} centered={true}/>;
    }

    return (
        <PageContentBlock>
            {servers.length > 0 ?
                servers.map(server => (
                    <ServerRow key={server.uuid} server={server} className={'mt-2'}/>
                ))
                :
                <p className={'text-center text-sm text-neutral-400'}>
                    It looks like you have no servers.
                </p>
            }
        </PageContentBlock>
    );
};
