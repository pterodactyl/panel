import React from 'react';
import { Hook } from '@/api/server/hooks/getServerHooks';
import tw from 'twin.macro';

export default ({ hook }: { hook: Hook }) => {
    const triggerType = hook.trigger?.type || 'Unknown';
    const triggerConfig = hook.trigger?.config ? Object.values(hook.action.config).join(', ') : '';
    const actionType = hook.action?.type || 'Unknown';
    const actionConfig = hook.action?.config ? Object.values(hook.action.config).join(', ') : '';
    console.log(hook);
    return (
        <>
            <div css={tw`flex-1 md: ml-4`}>
                <p>{hook.name}</p>
                {hook.trigger && (
                    <p css={tw`text-xs text-neutral-300`}>
                        Trigger: <span css={tw`font-medium`}>{triggerType}</span>
                        {triggerConfig && (
                            <>
                                {' '}
                                → <span>{triggerConfig}</span>
                            </>
                        )}
                    </p>
                )}
                {hook.action && (
                    <p css={tw`text-xs text-neutral-300`}>
                        Action: <span css={tw`font-medium`}>{actionType}</span>
                        {actionConfig && (
                            <>
                                {' '}
                                → <span>{actionConfig}</span>
                            </>
                        )}
                    </p>
                )}
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
};
