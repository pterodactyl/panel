import React from 'react';
import { UserIcon } from '@heroicons/react/outline';
import { Link } from 'react-router-dom';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Translate from '@/components/elements/Translate';
import { format, formatDistanceToNowStrict } from 'date-fns';
import { ActivityLog } from '@definitions/user';
import { useLocation } from 'react-router';
import ActivityLogMetaButton from '@/components/elements/activity/ActivityLogMetaButton';

interface Props {
    activity: ActivityLog;
    children?: React.ReactNode;
}

export default ({ activity, children }: Props) => {
    const location = useLocation();
    const actor = activity.relationships.actor;

    const queryTo = (params: Record<string, string>): string => {
        const current = new URLSearchParams(location.search);
        Object.keys(params).forEach(key => current.set(key, params[key]));

        return current.toString();
    };

    return (
        <div className={'grid grid-cols-10 py-4 border-b-2 border-gray-800 last:rounded-b last:border-0 group'}>
            <div className={'col-span-2 sm:col-span-1 flex items-center justify-center select-none'}>
                <div className={'flex items-center w-8 h-8 rounded-full bg-gray-600 overflow-hidden'}>
                    {actor ?
                        <img src={actor.image} alt={'User avatar'}/>
                        :
                        <UserIcon className={'w-5 h-5 mx-auto'}/>
                    }
                </div>
            </div>
            <div className={'col-span-8 sm:col-span-9 flex'}>
                <div className={'flex-1'}>
                    <div className={'flex items-center text-gray-50'}>
                        <Tooltip placement={'top'} content={actor?.email || 'System User'}>
                            <span>{actor?.username || 'System'}</span>
                        </Tooltip>
                        <span className={'text-gray-400'}>&nbsp;&mdash;&nbsp;</span>
                        <Link
                            to={`?${queryTo({ event: activity.event })}`}
                            className={'transition-colors duration-75 active:text-cyan-400 hover:text-cyan-400'}
                        >
                            {activity.event}
                        </Link>
                        {children}
                    </div>
                    <p className={'mt-1 text-sm break-words line-clamp-2 pr-4'}>
                        <Translate ns={'activity'} values={activity.properties}>
                            {activity.event.replace(':', '.')}
                        </Translate>
                    </p>
                    <div className={'mt-1 flex items-center text-sm'}>
                        <Link
                            to={`?${queryTo({ ip: activity.ip })}`}
                            className={'transition-colors duration-75 active:text-cyan-400 hover:text-cyan-400'}
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
                <ActivityLogMetaButton meta={activity.properties}/>
            </div>
        </div>
    );
};
