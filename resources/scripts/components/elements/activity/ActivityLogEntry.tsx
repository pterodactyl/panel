import React from 'react';
import { Link } from '@tanstack/react-router';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Translate from '@/components/elements/Translate';
import type { TranslationValue, TranslationValues } from '@/components/elements/Translate';
import dayjs from '@/lib/dayjs';
import ActivityLogMetaButton from '@/components/elements/activity/ActivityLogMetaButton';
import { FolderOpen, Terminal } from 'lucide-react';
import { cn } from '@/lib/cn';
import Avatar from '@/components/Avatar';
import type { ActivityLog } from '@/api/activity';
import { isNumber, isString } from '@/lib/objects';
import { relationshipAttributes } from '@/api/relationships';

type ActorAttributes = { username?: string; email?: string };

interface Props {
    activity: ActivityLog;
    children?: React.ReactNode;
}

const activityIconClass =
    'flex space-x-1 mx-2 transition-colors duration-100 text-muted-foreground [&_svg]:px-1 [&_svg]:py-px [&_svg]:cursor-pointer [&_svg]:h-5 [&_svg]:w-auto [&_svg]:hover:text-foreground';

const activityDescriptionClass =
    'mt-1 text-sm break-words line-clamp-2 pr-4 [&_strong]:break-all [&_strong]:font-semibold [&_strong]:text-foreground';

function wrapProperty(value: TranslationValue): TranslationValue {
    if (value === null || isString(value) || isNumber(value)) {
        return `<strong>${String(value)}</strong>`;
    }

    if (Array.isArray(value)) {
        return value.map(wrapProperty);
    }

    if (value === true || value === false) {
        return value;
    }

    return Object.fromEntries(
        Object.entries(value).map(([key, item]) => [
            key,
            key === 'count' || key.endsWith('_count') ? item : wrapProperty(item),
        ])
    );
}

const wrapProperties = (properties: TranslationValues): TranslationValues =>
    Object.fromEntries(Object.entries(properties).map(([key, value]) => [key, wrapProperty(value)]));

export default function ActivityLogEntry({ activity, children }: Props) {
    const { attributes } = activity;
    const actor = relationshipAttributes<ActorAttributes>(attributes.relationships?.actor);
    const properties = wrapProperties(attributes.properties);

    return (
        <div className='grid grid-cols-10 py-4 border-b-2 border-border last:rounded-b-sm last:border-0 group'>
            <div className='hidden sm:flex sm:col-span-1 items-center justify-center select-none'>
                <div className='flex items-center w-10 h-10 rounded-full bg-popover overflow-hidden'>
                    <Avatar name={actor?.username || actor?.email || 'system'} />
                </div>
            </div>
            <div className='col-span-10 sm:col-span-9 flex'>
                <div className='flex-1 px-4 sm:px-0'>
                    <div className='flex items-center text-foreground'>
                        <Tooltip placement='top' content={actor?.email || 'System User'}>
                            <span>{actor?.username || 'System'}</span>
                        </Tooltip>
                        <span className='text-muted-foreground'>&nbsp;&mdash;&nbsp;</span>
                        <Link
                            to='.'
                            search={{ event: attributes.event }}
                            className='transition-colors duration-75 active:text-accent hover:text-accent'
                        >
                            {attributes.event}
                        </Link>
                        <div className={cn(activityIconClass, 'group-hover:text-muted-foreground')}>
                            {attributes.is_api && (
                                <Tooltip placement='top' content='Using API Key'>
                                    <Terminal />
                                </Tooltip>
                            )}
                            {attributes.event.startsWith('server:sftp.') && (
                                <Tooltip placement='top' content='Using SFTP'>
                                    <FolderOpen />
                                </Tooltip>
                            )}
                            {children}
                        </div>
                    </div>
                    <p className={activityDescriptionClass}>
                        <Translate ns='activity' values={properties} i18nKey={attributes.event.replace(':', '.')} />
                    </p>
                    <div className='mt-1 flex items-center text-sm'>
                        {attributes.ip && (
                            <span>
                                {attributes.ip}
                                <span className='text-muted-foreground'>&nbsp;|&nbsp;</span>
                            </span>
                        )}
                        <Tooltip placement='right' content={dayjs(attributes.timestamp).format('MMM Do, YYYY H:mm:ss')}>
                            <span>{dayjs(attributes.timestamp).fromNow()}</span>
                        </Tooltip>
                    </div>
                </div>
                {attributes.has_additional_metadata && <ActivityLogMetaButton meta={attributes.properties} />}
            </div>
        </div>
    );
}
