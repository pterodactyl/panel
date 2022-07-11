import classNames from 'classnames';
import * as Icon from 'react-feather';
import { Link } from 'react-router-dom';
import { useFlashKey } from '@/plugins/useFlash';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import useLocationHash from '@/plugins/useLocationHash';
import { useActivityLogs } from '@/api/server/activity';
import { ActivityLogFilters } from '@/api/account/activity';
import FlashMessageRender from '@/components/FlashMessageRender';
import { styles as btnStyles } from '@/components/elements/button/index';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import ActivityLogEntry from '@/components/elements/activity/ActivityLogEntry';

export default () => {
    const { hash } = useLocationHash();
    const { clearAndAddHttpError } = useFlashKey('server:activity');
    const [filters, setFilters] = useState<ActivityLogFilters>({ page: 1, sorts: { timestamp: -1 } });

    const { data, isValidating, error } = useActivityLogs(filters, {
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        setFilters((value) => ({ ...value, filters: { ip: hash.ip, event: hash.event } }));
    }, [hash]);

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [error]);

    return (
        <ServerContentBlock title={'Activity Log'}>
            <FlashMessageRender byKey={'server:activity'} />
            <h1 className={'j-left text-5xl'}>Server Activity</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500 mb-10'}>View activity on this server.</h3>
            {(filters.filters?.event || filters.filters?.ip) && (
                <div className={'flex justify-end mb-2'}>
                    <Link
                        to={'#'}
                        className={classNames(btnStyles.button, btnStyles.text, 'w-full sm:w-auto')}
                        onClick={() => setFilters((value) => ({ ...value, filters: {} }))}
                    >
                        Clear Filters <Icon.XCircle className={'w-4 h-4 ml-2'} />
                    </Link>
                </div>
            )}
            {!data && isValidating ? (
                <Spinner centered />
            ) : !data?.items.length ? (
                <p className={'j-up text-sm text-center text-gray-400'}>No activity logs available for this server.</p>
            ) : (
                <div className={'bg-neutral-900 j-up'}>
                    {data?.items.map((activity) => (
                        <ActivityLogEntry key={activity.id} activity={activity}>
                            <span />
                        </ActivityLogEntry>
                    ))}
                </div>
            )}
            {data && (
                <PaginationFooter
                    pagination={data.pagination}
                    onPageSelect={(page) => setFilters((value) => ({ ...value, page }))}
                />
            )}
        </ServerContentBlock>
    );
};
