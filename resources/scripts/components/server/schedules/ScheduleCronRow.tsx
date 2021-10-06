import React from 'react';
import tw from 'twin.macro';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import { useTranslation } from 'react-i18next';

interface Props {
    cron: Schedule['cron'];
    className?: string;
}

const ScheduleCronRow = ({ cron, className }: Props) => {
    const { t } = useTranslation();
    return(
    <div css={tw`flex`} className={className}>
        <div css={tw`w-1/5 sm:w-auto text-center`}>
            <p css={tw`font-medium`}>{cron.minute}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>{t('Schedule Row Minute')}</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.hour}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>{t('Schedule Row Hour')}</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.dayOfMonth}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>{t('Schedule Row Day Of Month')}</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.month}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>{t('Schedule Row Month')}</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.dayOfWeek}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>{t('Schedule Row Day Of Week')}</p>
        </div>
    </div>
)};

export default ScheduleCronRow;
