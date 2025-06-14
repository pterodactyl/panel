import React from 'react';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import RenewServerBox from '@/components/server/settings/RenewServerBox';
import { useLocation } from 'react-router';

export default () => {
    const location = useLocation();
    let status = '';

    if (location.pathname.startsWith('/server')) {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        status = ServerContext.useStoreState((state) => state.server.data?.status || null);
    }

    return (
        <>
            {location.pathname.startsWith('/server') && (
                <>
                    {status === 'suspended' && (
                        <>
                            <div css={tw`flex justify-center`}>
                                <div css={tw`w-full lg:w-1/2 pt-4`}>
                                    <FlashMessageRender key={'settings'} />
                                </div>
                            </div>
                            <div css={tw`flex justify-center`}>
                                <div css={tw`w-full lg:w-1/2 pt-4`}>
                                    <RenewServerBox />
                                </div>
                            </div>
                        </>
                    )}
                </>
            )}
        </>
    );
};
