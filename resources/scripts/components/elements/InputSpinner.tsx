import React from 'react';
import Spinner from '@/components/elements/Spinner';
import { cn } from '@/lib/cn';

const InputSpinner = ({ visible, children }: { visible: boolean; children: React.ReactNode }) => (
    // Hides a native select's arrow behind the spinner.
    <div className={cn('relative', visible && '[&_select]:bg-none')}>
        {visible && (
            <div className={'absolute right-0 h-full flex items-center justify-end pr-3'}>
                <Spinner size={'small'} />
            </div>
        )}
        {children}
    </div>
);

export default InputSpinner;
