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
                                                <div css={tw`flex-initial ml-16 text-center`}>
                                                    <p css={tw`text-sm`}>{item.action}</p>
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
