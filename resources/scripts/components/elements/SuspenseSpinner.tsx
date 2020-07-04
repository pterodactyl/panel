import React, { Suspense } from 'react';
import Spinner from '@/components/elements/Spinner';

const SuspenseSpinner = ({ children }: { children?: React.ReactNode }) => (
    <Suspense
        fallback={
            <div className={'mx-4 w-3/4 mr-4 flex items-center justify-center'}>
                <Spinner centered/>
            </div>
        }
    >
        {children}
    </Suspense>
);

export default SuspenseSpinner;
