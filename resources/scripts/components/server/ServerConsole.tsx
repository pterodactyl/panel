import React from 'react';
import Console from '@/components/server/Console';

export default () => (
    <div className={'my-10 flex'}>
        <div className={'mx-4 w-3/4 mr-4'}>
            <Console/>
        </div>
        <div className={'flex-1 ml-4'}>
            <p>Testing</p>
        </div>
    </div>
);
