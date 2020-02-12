import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCalendarAlt } from '@fortawesome/free-solid-svg-icons/faCalendarAlt';
import format from 'date-fns/format';
import classNames from 'classnames';

export default ({ schedule }: { schedule: Schedule }) => (
    <>
        <div className={'icon'}>
            <FontAwesomeIcon icon={faCalendarAlt} fixedWidth={true}/>
        </div>
        <div className={'flex-1 ml-4'}>
            <p>{schedule.name}</p>
            <p className={'text-xs text-neutral-400'}>
                Last run
                at: {schedule.lastRunAt ? format(schedule.lastRunAt, 'MMM Do [at] h:mma') : 'never'}
            </p>
        </div>
        <div className={'flex items-center mx-8'}>
            <div>
                <p className={'font-medium text-center'}>{schedule.cron.minute}</p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Minute</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>{schedule.cron.hour}</p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Hour</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>{schedule.cron.dayOfMonth}</p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Day (Month)</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>*</p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Month</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>{schedule.cron.dayOfWeek}</p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Day (Week)</p>
            </div>
        </div>
        <div>
            <p
                className={classNames('py-1 px-3 rounded text-xs uppercase', {
                    'bg-green-600': schedule.isActive,
                    'bg-neutral-400': !schedule.isActive,
                })}
            >
                {schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
    </>
);
