import React, { Suspense } from 'react';
import Spinner from '@/components/elements/Spinner';

const SuspenseSpinner: React.FC = ({ children }) => (
    <Suspense fallback={<Spinner size={'large'} centered/>}>
        {children}
    </Suspense>
);

export default SuspenseSpinner;
