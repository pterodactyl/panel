import React from 'react';
import Console from '@/components/server/Console';
import { ServerContext } from '@/state/server';

export default () => {
    const status = ServerContext.useStoreState(state => state.status.value);

    return (
        <div className={'my-10 flex'}>
            <div className={'mx-4 w-3/4 mr-4'}>
                <Console/>
            </div>
            <div className={'flex-1 ml-4'}>
                <p>Current status: {status}</p>
            </div>
        </div>
    );
};
