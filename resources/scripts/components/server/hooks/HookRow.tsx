import React from 'react';
import { Hook } from '@/api/server/hooks/getServerHooks';
import tw from 'twin.macro';

export default ({ hook }: { hook: Hook }) => (
    <>
        <div css={tw`flex-1 md:ml-4`}>
            <p>{hook.name}</p>
        </div>
        <div>
            <p
                css={[
                    tw`py-1 px-3 rounded text-xs uppercase text-white sm:hidden`,
                    hook.enabled ? tw`bg-green-600` : tw`bg-neutral-400`,
                ]}
            >
                {hook.enabled ? 'Active' : 'Inactive'}
            </p>
        </div>
        <div>
            <p
                css={[
                    tw`py-1 px-3 rounded text-xs uppercase text-white hidden sm:block`,
                    hook.enabled ? tw`bg-green-600` : tw`bg-neutral-400`,
                ]}
            >
                {hook.enabled ? 'Active' : 'Inactive'}
            </p>
        </div>
    </>
);
