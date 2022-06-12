import React, { useEffect, useState } from 'react';
import { useActivityLogs } from '@/api/server/activity';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { useFlashKey } from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';
import ActivityLogEntry from '@/components/elements/activity/ActivityLogEntry';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import { ActivityLogFilters } from '@/api/account/activity';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import { styles as btnStyles } from '@/components/elements/button';
import { XCircleIcon } from '@heroicons/react/solid';

export default () => {
    const { clearAndAddHttpError } = useFlashKey('server:activity');
    const [ filters, setFilters ] = useState<ActivityLogFilters>({ page: 1, sorts: { timestamp: -1 } });

    const { data, isValidating, error } = useActivityLogs(filters, {
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        const parsed = new URLSearchParams(location.search);

        setFilters(value => ({ ...value, filters: { ip: parsed.get('ip'), event: parsed.get('event') } }));
    }, [ location.search ]);

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [ error ]);

    return (
        <ServerContentBlock title={'Activity Log'}>
            <FlashMessageRender byKey={'server:activity'}/>
            {(filters.filters?.event || filters.filters?.ip) &&
                <div className={'flex justify-end mb-2'}>
                    <Link
                        to={'#'}
                        className={classNames(btnStyles.button, btnStyles.text, 'w-full sm:w-auto')}
                        onClick={() => setFilters(value => ({ ...value, filters: {} }))}
                    >
                        Clear Filters <XCircleIcon className={'w-4 h-4 ml-2'}/>
                    </Link>
                </div>
            }
            {!data && isValidating ?
                <Spinner centered/>
                :
                <div className={'bg-gray-700'}>
                    {data?.items.map((activity) => (
                        <ActivityLogEntry key={activity.timestamp.toString() + activity.event} activity={activity}>
                            <span/>
                        </ActivityLogEntry>
                    ))}
                </div>
            }
            {data && <PaginationFooter
                pagination={data.pagination}
                onPageSelect={page => setFilters(value => ({ ...value, page }))}
            />}
        </ServerContentBlock>
    );
};
