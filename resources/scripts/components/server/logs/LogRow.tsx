import React from 'react';
import { formatDistanceToNow } from 'date-fns';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { ServerLog } from '@/api/server/types';
import { LogHandler } from '@/components/server/logs/LogHandler';

interface Props {
    log: ServerLog;
    className?: string;
}

export default ({ log, className }: Props) => {
    return (
        <GreyRowBox css={tw`mb-2 w-full`} className={className}>
            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-sm`}>{LogHandler(log)}</p>
                <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Action</p>
            </div>
            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-sm`}>{log.user}</p>
                <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>User</p>
            </div>
            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-sm`}>{formatDistanceToNow(log.createdAt, { includeSeconds: true, addSuffix: true })}</p>
                <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Created</p>
            </div>
        </GreyRowBox>
    );
};
