import { ExclamationIcon } from '@heroicons/react/outline';
import React from 'react';
import classNames from 'classnames';

interface AlertProps {
    type: 'warning';
    className?: string;
    children: React.ReactNode;
}

export default ({ className, children }: AlertProps) => {
    return (
        <div
            className={classNames(
                'flex items-center border-l-8 border-yellow-500 text-gray-50 bg-yellow-500/25 rounded-md shadow px-4 py-3',
                className
            )}
        >
            <ExclamationIcon className={'w-6 h-6 text-yellow-500 mr-2'} />
            {children}
        </div>
    );
};
