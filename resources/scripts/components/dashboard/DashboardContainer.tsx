import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons/faServer';
import { faMicrochip } from '@fortawesome/free-solid-svg-icons/faMicrochip';
import { faMemory } from '@fortawesome/free-solid-svg-icons/faMemory';
import { faHdd } from '@fortawesome/free-solid-svg-icons/faHdd';
import { faEthernet } from '@fortawesome/free-solid-svg-icons/faEthernet';
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
