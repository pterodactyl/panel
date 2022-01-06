import React, { useContext, useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import LogRow from '@/components/server/logs/LogRow';
import tw from 'twin.macro';
import getServerLogs, { Context as ServerLogsContext } from '@/api/swr/getServerLogs';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Pagination from '@/components/elements/Pagination';

const LogsContainer = () => {
    const { page, setPage } = useContext(ServerLogsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: logs, error, isValidating } = getServerLogs();

    useEffect(() => {
        if (!error) {
            clearFlashes('logs');

            return;
        }

        clearAndAddHttpError({ error, key: 'logs' });
    }, [ error ]);

    if (!logs || (error && isValidating)) {
        return <Spinner size={'large'} centered/>;
    }

    console.log(logs)
    
    return (
        <ServerContentBlock title={'Audit Logs'}>
            <FlashMessageRender byKey={'logs'} css={tw`mb-4`}/>
            <Pagination data={logs} onPageSelect={setPage}>
                {({ items }) => (
                    !items.length ?
                            <p css={tw`text-center text-sm text-neutral-300`}>
                                {page > 1 ?
                                    'Looks like we\'ve run out of logs to show you, try going back a page.'
                                    :
                                    'It looks like there are no logs currently stored for this server.'
                                }
                            </p>
                        :
                        items.map((log, index) => <LogRow
                            key={log.uuid}
                            log={log}
                            css={index > 0 ? tw`mt-2` : undefined}
                        />)
                )}
            </Pagination>
        </ServerContentBlock>
    );
};

export default () => {
    const [ page, setPage ] = useState<number>(1);
    return (
        <ServerLogsContext.Provider value={{ page, setPage }}>
            <LogsContainer/>
        </ServerLogsContext.Provider>
    );
};
