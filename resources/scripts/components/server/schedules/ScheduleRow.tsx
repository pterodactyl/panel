import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCalendarAlt } from '@fortawesome/free-solid-svg-icons';
import { format } from 'date-fns';
import tw from 'twin.macro';

export default ({ schedule }: { schedule: Schedule }) => (
    <>
        <div>
            <FontAwesomeIcon icon={faCalendarAlt} fixedWidth/>
        </div>
        <div css={tw`flex-1 ml-4`}>
            <p>{schedule.name}</p>
            <p css={tw`text-xs text-neutral-400`}>
                Last run
                at: {schedule.lastRunAt ? format(schedule.lastRunAt, 'MMM do \'at\' h:mma') : 'never'}
            </p>
        </div>
        <div css={tw`flex items-center mx-8`}>
            <div>
                <p css={tw`font-medium text-center`}>{schedule.cron.minute}</p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Minute</p>
            </div>
            <div css={tw`ml-4`}>
                <p css={tw`font-medium text-center`}>{schedule.cron.hour}</p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Hour</p>
            </div>
            <div css={tw`ml-4`}>
                <p css={tw`font-medium text-center`}>{schedule.cron.dayOfMonth}</p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Day (Month)</p>
            </div>
            <div css={tw`ml-4`}>
                <p css={tw`font-medium text-center`}>*</p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Month</p>
            </div>
            <div css={tw`ml-4`}>
                <p css={tw`font-medium text-center`}>{schedule.cron.dayOfWeek}</p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Day (Week)</p>
            </div>
        </div>
        <div>
            <p
                css={[
                    tw`py-1 px-3 rounded text-xs uppercase text-white`,
                    schedule.isActive ? tw`bg-green-600` : tw`bg-neutral-400`,
                ]}
            >
                {schedule.isActive ? 'Active' : 'Inactive'}
            </p>
        </div>
    </>
);
