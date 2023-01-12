import { ExclamationIcon, ShieldExclamationIcon } from '@heroicons/react/outline';
import * as React from 'react';
import classNames from 'classnames';

interface AlertProps {
    type: 'warning' | 'danger';
    className?: string;
    children: React.ReactNode;
}

export default ({ type, className, children }: AlertProps) => {
    return (
        <div
            className={classNames(
                'flex items-center rounded-md border-l-8 px-4 py-3 text-slate-50 shadow',
                {
                    ['border-red-500 bg-red-500/25']: type === 'danger',
                    ['border-yellow-500 bg-yellow-500/25']: type === 'warning',
                },
                className,
            )}
        >
            {type === 'danger' ? (
                <ShieldExclamationIcon className={'mr-2 h-6 w-6 text-red-400'} />
            ) : (
                <ExclamationIcon className={'mr-2 h-6 w-6 text-yellow-500'} />
            )}
            {children}
        </div>
    );
};
