import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCalendarAlt } from '@fortawesome/free-solid-svg-icons';
import { format } from 'date-fns';
import tw from 'twin.macro';
import ScheduleCronRow from '@/components/server/schedules/ScheduleCronRow';

export default ({ schedule }: { schedule: Schedule }) => (
    <>
        <div css={tw`hidden md:block`}>
            <FontAwesomeIcon icon={faCalendarAlt} fixedWidth />
        </div>
        <div css={tw`flex-1 md:ml-4`}>
            <p>{schedule.name}</p>
            <p css={tw`text-xs text-neutral-400`}>
                Last run at: {schedule.lastRunAt ? format(schedule.lastRunAt, "MMM do 'at' h:mma") : 'never'}
            </p>
        </div>
        <div>
            <p
                css={[
                    tw`py-1 px-3 rounded text-xs uppercase text-white sm:hidden`,
                    schedule.isActive ? tw`bg-green-600` : tw`bg-neutral-400`,
                ]}
            >
                {schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
        <ScheduleCronRow cron={schedule.cron} css={tw`mx-auto sm:mx-8 w-full sm:w-auto mt-4 sm:mt-0`} />
        <div>
            <p
                css={[
                    tw`py-1 px-3 rounded text-xs uppercase text-white hidden sm:block`,
                    schedule.isActive && !schedule.isProcessing ? tw`bg-green-600` : tw`bg-neutral-400`,
                ]}
            >
                {schedule.isProcessing ? 'Processing' : schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
    </>
);
