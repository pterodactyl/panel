import React from 'react';
import Console from '@/components/server/Console';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';

export default () => {
    const status = useStoreState((state: State<ApplicationState>) => state.server.status);

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
