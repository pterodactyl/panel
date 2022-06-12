import React, { useEffect, useState } from 'react';
import { ActivityLogFilters, useActivityLogs } from '@/api/account/activity';
import { useFlashKey } from '@/plugins/useFlash';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import { format, formatDistanceToNowStrict } from 'date-fns';
import { Link } from 'react-router-dom';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import { UserIcon } from '@heroicons/react/outline';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { DesktopComputerIcon, XCircleIcon } from '@heroicons/react/solid';
import { useLocation } from 'react-router';
import Spinner from '@/components/elements/Spinner';
import { styles as btnStyles } from '@/components/elements/button/index';
import classNames from 'classnames';
import Translate from '@/components/elements/Translate';

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

    const queryTo = (params: Record<string, string>): string => {
        const current = new URLSearchParams(location.search);
        Object.keys(params).forEach(key => {
            current.set(key, params[key]);
        });

        return current.toString();
    };

    return (
        <PageContentBlock title={'Account Activity Log'}>
            <FlashMessageRender byKey={'account'}/>
            {(filters.filters?.event || filters.filters?.ip) &&
                <div className={'flex justify-end mb-2'}>
                    <Link
                        to={'#'}
                        className={classNames(btnStyles.button, btnStyles.text)}
                        onClick={() => setFilters(value => ({ ...value, filters: {} }))}
                    >
                        重设过滤器 <XCircleIcon className={'w-4 h-4 ml-2'}/>
                    </Link>
                </div>
            }
            {!data && isValidating ?
                <Spinner centered/>
                :
                <div className={'bg-gray-700'}>
                    {data?.items.map((activity) => (
                        <div
                            key={`${activity.event}|${activity.timestamp.toString()}`}
                            className={'grid grid-cols-10 py-4 border-b-2 border-gray-800 last:rounded-b last:border-0'}
                        >
                            <div className={'col-span-1 flex items-center justify-center select-none'}>
                                <div className={'flex items-center w-8 h-8 rounded-full bg-gray-600 overflow-hidden'}>
                                    {activity.relationships.actor ?
                                        <img src={activity.relationships.actor.image} alt={'User avatar'}/>
                                        :
                                        <UserIcon className={'w-5 h-5 mx-auto'}/>
                                    }
                                </div>
                            </div>
                            <div className={'col-span-9'}>
                                <div className={'flex items-center text-gray-50'}>
                                    {activity.relationships.actor?.username || 'system'}
                                    <span className={'text-gray-400'}>&nbsp;&mdash;&nbsp;</span>
                                    <Link
                                        to={`?${queryTo({ event: activity.event })}`}
                                        className={'transition-colors duration-75 hover:text-cyan-400'}
                                    >
                                        {activity.event}
                                    </Link>
                                    {typeof activity.properties.useragent === 'string' &&
                                        <Tooltip content={activity.properties.useragent} placement={'top'}>
                                            <DesktopComputerIcon className={'ml-2 w-4 h-4 cursor-pointer'}/>
                                        </Tooltip>
                                    }
                                </div>
                                <p className={'mt-1 text-sm'}>
                                    <Translate ns={'activity'} values={activity.properties}>
                                        {activity.event.replace(':', '.')}
                                    </Translate>
                                </p>
                                <div className={'mt-1 flex items-center text-sm'}>
                                    <Link
                                        to={`?${queryTo({ ip: activity.ip })}`}
                                        className={'transition-colors duration-75 hover:text-cyan-400'}
                                    >
                                        {activity.ip}
                                    </Link>
                                    <span className={'text-gray-400'}>&nbsp;|&nbsp;</span>
                                    <Tooltip
                                        placement={'right'}
                                        content={format(activity.timestamp, 'MMM do, yyyy h:mma')}
                                    >
                                        <span>
                                            {formatDistanceToNowStrict(activity.timestamp, { addSuffix: true })}
                                        </span>
                                    </Tooltip>
                                </div>
                            </div>
                        </div>
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
