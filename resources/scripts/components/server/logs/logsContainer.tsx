import React, { useEffect } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import useSWR from 'swr';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';
import getLogsRequests from '@/api/server/logs/getlogs';

export interface LogsResponse {
    requests: any[],
}

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error } = useSWR<LogsResponse>([ uuid, '/logs' ], key => getLogsRequests(key));

    useEffect(() => {
        if (!error) {
            clearFlashes('server:logs');
        } else {
            clearAndAddHttpError({ key: 'server:logs', error });
        }
    }, [ error ]);

    return (
        <ServerContentBlock title={'Audit Logs'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'server:logs'} css={tw`mb-4`} />
            </div>
            {!data ?
                (
                    <div css={tw`w-full`}>
                        <Spinner size={'large'} centered />
                    </div>
                )
                :
                (
                    <>
                        <div css={tw`w-full`}>
                            {data.requests.length < 1 ?
                                <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>
                                    There are no logs for this server.
                                </p>
                                :
                                (data.requests.map((item, key) => (
                                    <GreyRowBox $hoverable={false} css={tw`mb-2`} key={key}>
                                        <GreyRowBox $hoverable={false} key={key}>
                                            <div css={tw`flex items-center w-full md:w-auto`}>          
                                                <div css={tw`flex-initial ml-16 text-center`}>
                                                    <p css={tw`text-sm`}>{item.user_id}</p>
                                                    <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>User</p>
                                                </div>
                                                <div css={tw`flex-initial ml-4 text-center`}>
                                                    {item.action === 'server:filesystem.download' ?
                                                        <span css={tw`text-sm`}>Filesystem Download</span>
                                                        : item.action === 'server:filesystem.write' ?
                                                            <span css={tw`text-sm`}>Filesystem Write</span>
                                                            : item.action === 'server:filesystem.delete' ?
                                                                <span css={tw`text-sm`}>Filesystem Delete</span>
                                                                : item.action === 'server:filesystem.rename' ?
                                                                    <span css={tw`text-sm`}>Filesystem Rename</span>
                                                                    : item.action === 'server:filesystem.compress' ?
                                                                        <span css={tw`text-sm`}>Filesystem Compress</span>
                                                                        : item.action === 'server:filesystem.decompress' ?
                                                                            <span css={tw`text-sm`}>Filesystem Decompress</span>
                                                                            : item.action === 'server:filesystem.pull' ?
                                                                                <span css={tw`text-sm`}>Filesystem Pull</span>
                                                                                : item.action === 'server:backup.started' ?
                                                                                    <span css={tw`text-sm`}>Backup Started</span>
                                                                                    : item.action === 'server:backup.failed' ?
                                                                                        <span css={tw`text-sm`}>Backup Failed</span>
                                                                                        : item.action === 'server:backup.completed' ?
                                                                                            <span css={tw`text-sm`}>Backup Completed</span>
                                                                                            : item.action === 'server:backup.deleted' ?
                                                                                                <span css={tw`text-sm`}>Backup Deleted</span>
                                                                                                : item.action === 'server:backup.downloaded' ?
                                                                                                    <span css={tw`text-sm`}>Backup Downloaded</span>
                                                                                                    : item.action === 'server:backup.locked' ?
                                                                                                        <span css={tw`text-sm`}>Backup Locked</span>
                                                                                                        : item.action === 'server:backup.unlocked' ?
                                                                                                            <span css={tw`text-sm`}>Backup Unlocked</span>
                                                                                                            : item.action === 'server:backup.restore.started' ?
                                                                                                                <span css={tw`text-sm`}>Backup Restore Started</span>
                                                                                                                : item.action === 'server:backup.restore.completed' ?
                                                                                                                    <span css={tw`text-sm`}>Backup Restore Completed</span>
                                                                                                                    : item.action === 'server:backup.restore.failed' ?
                                                                                                                        <span css={tw`text-sm`}>Backup Restore Failed</span>

                                                                                                                        : null
                                                    }
                                                    <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Action</p>
                                                </div>      
                                                <div css={tw`flex-initial ml-16 text-center`}>
                                                    <p css={tw`text-sm`}>{item.created_at}</p>
                                                    <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Date</p>
                                                </div>
                                                <div css={tw`flex-initial ml-16 text-center`}>
                                                    <p css={tw`text-sm`}>{item.metadata}</p>
                                                    <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>File</p>
                                                </div>
                                            </div>
                                        </GreyRowBox>
                                    </GreyRowBox>
                                )))
                            }
                        </div>

                    </>
                )
            }
        </ServerContentBlock>
    );
};
