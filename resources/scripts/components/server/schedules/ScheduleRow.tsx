import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCalendarAlt } from '@fortawesome/free-solid-svg-icons';
import { format } from 'date-fns';
import ScheduleCronRow from '@/components/server/schedules/ScheduleCronRow';
import classNames from 'classnames';

export default ({ schedule }: { schedule: Schedule }) => (
    <>
        <div className="hidden md:block">
            <FontAwesomeIcon icon={faCalendarAlt} fixedWidth />
        </div>
        <div className="flex-1 md:ml-4">
            <p>{schedule.name}</p>
            <p className="text-xs text-neutral-400">
                Last run at: {schedule.lastRunAt ? format(schedule.lastRunAt, "MMM do 'at' h:mma") : 'never'}
            </p>
        </div>
        <div>
            <p
                className={classNames('py-1 px-3 rounded text-xs uppercase text-white sm:hidden', schedule.isActive ? 'bg-green-600' : 'bg-neutral-400')}
            >
                {schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
        <ScheduleCronRow cron={schedule.cron} className="mx-auto sm:mx-8 w-full sm:w-auto mt-4 sm:mt-0" />
        <div>
            <p
                className={classNames('py-1 px-3 rounded text-xs uppercase text-white hidden sm:block', schedule.isActive && !schedule.isProcessing ? 'bg-green-600' : 'bg-neutral-400')}
            >
                {schedule.isProcessing ? 'Processing' : schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
    </>
);
