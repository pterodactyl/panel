import React, { useEffect, useState } from 'react';
import { useActivityLogs } from '@/api/account/activity';
import { useFlashKey } from '@/plugins/useFlash';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import { format, formatDistanceToNowStrict } from 'date-fns';
import { Link } from 'react-router-dom';
import PaginationFooter from '@/components/elements/table/PaginationFooter';
import { UserIcon } from '@heroicons/react/outline';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { DesktopComputerIcon } from '@heroicons/react/solid';

export default () => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const [ page, setPage ] = useState(1);
    const { data, isValidating: _, error } = useActivityLogs(page, {
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [ error ]);

    return (
        <PageContentBlock title={'Account Activity Log'}>
            <FlashMessageRender byKey={'account'}/>
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
                                <Link to={`#event=${activity.event}`}>
                                    {activity.event}
                                </Link>
                                {typeof activity.properties.useragent === 'string' &&
                                    <Tooltip content={activity.properties.useragent} placement={'top'}>
                                        <DesktopComputerIcon className={'ml-2 w-4 h-4 cursor-pointer'}/>
                                    </Tooltip>
                                }
                            </div>
                            {/* <p className={'mt-1'}>{activity.description || JSON.stringify(activity.properties)}</p> */}
                            <div className={'mt-1 flex items-center text-sm'}>
                                <Link to={`#ip=${activity.ip}`}>{activity.ip}</Link>
                                <span className={'text-gray-400'}>&nbsp;|&nbsp;</span>
                                <Tooltip placement={'right'} content={format(activity.timestamp, 'MMM do, yyyy h:mma')}>
                                    <span>
                                        {formatDistanceToNowStrict(activity.timestamp, { addSuffix: true })}
                                    </span>
                                </Tooltip>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            {data && <PaginationFooter pagination={data.pagination} onPageSelect={setPage}/>}
        </PageContentBlock>
    );
};
