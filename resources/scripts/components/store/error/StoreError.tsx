import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { useStoreState } from '@/state/hooks';

export default () => {
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);

    return (
        <>
            <div css={tw`flex items-center justify-center w-full my-4`}>
                <div css={tw`flex items-center bg-neutral-900 rounded p-3 text-red-500`}>
                    <Icon.AlertCircle css={tw`h-4 w-auto mr-2`} />
                    <p css={tw`text-sm text-neutral-100`}>Unable to retrieve user resources. Retrying...</p>
                </div>
            </div>
            {rootAdmin && (
                <div css={tw`flex items-center justify-center w-full mt-4`}>
                    <div css={tw`flex items-center bg-neutral-900 rounded p-3 text-yellow-500`}>
                        <Icon.HelpCircle css={tw`h-4 w-auto mr-2`} />
                        <p css={tw`text-sm text-neutral-100`}>
                            Are your admin settings for the storefront correct?
                            <a href={'/admin/jexactyl'} css={tw`text-blue-500 ml-1`}>
                                Check
                            </a>
                        </p>
                    </div>
                </div>
            )}
        </>
    );
};
