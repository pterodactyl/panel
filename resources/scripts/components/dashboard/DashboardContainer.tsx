import React, { useEffect, useState } from 'react';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';

export default () => {
    const [ servers, setServers ] = useState<null | Server[]>(null);

    const loadServers = () => getServers().then(data => setServers(data.items));

    useEffect(() => {
        loadServers();
    }, []);

    if (servers === null) {
        return <Spinner size={'large'} centered={true}/>;
    }

    return (
        <div className={'my-10'}>
            {
                servers.map(server => (
                    <ServerRow key={server.uuid} server={server} className={'mt-2'}/>
                ))
            }
        </div>
    );
};
