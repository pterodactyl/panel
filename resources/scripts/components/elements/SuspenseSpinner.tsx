import React, { Suspense } from 'react';
import Spinner from '@/components/elements/Spinner';

export default ({ children }: { children?: React.ReactNode }) => (
    <Suspense
        fallback={
            <div className={'mx-4 w-3/4 mr-4 flex items-center justify-center'}>
                <Spinner centered={true} size={'normal'}/>
            </div>
        }
    >
        {children}
    </Suspense>
);
