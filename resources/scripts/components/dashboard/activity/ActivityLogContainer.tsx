import React, { useEffect, useState } from 'react';
import { ActivityLogFilters, useActivityLogs } from '@/api/account/activity';
import { useFlashKey } from '@/plugins/useFlash';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Link } from 'react-router-dom';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import { DesktopComputerIcon, XCircleIcon } from '@heroicons/react/solid';
import { useLocation } from 'react-router';
import Spinner from '@/components/elements/Spinner';
import { styles as btnStyles } from '@/components/elements/button/index';
import classNames from 'classnames';
import ActivityLogEntry from '@/components/elements/activity/ActivityLogEntry';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import tw from 'twin.macro'

export default () => {
    const location = useLocation();

    const { clearAndAddHttpError } = useFlashKey('account');
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
        <PageContentBlock title={'Account Activity Log'}>
            <h1 css={tw`text-5xl`}>Account Activity</h1>
            <h3 css={tw`text-2xl text-neutral-500`}>View your recent Activity</h3>
            <FlashMessageRender byKey={'account'}/>
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
                            {typeof activity.properties.useragent === 'string' &&
                                <Tooltip content={activity.properties.useragent} placement={'top'}>
                                    <span><DesktopComputerIcon/></span>
                                </Tooltip>
                            }
                        </ActivityLogEntry>
                    ))}
                </div>
            }
            {data && <PaginationFooter
                pagination={data.pagination}
                onPageSelect={page => setFilters(value => ({ ...value, page }))}
            />}
        </PageContentBlock>
    );
};
