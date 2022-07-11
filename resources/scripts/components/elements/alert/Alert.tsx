import React from 'react';
import classNames from 'classnames';
import { ExclamationIcon, InformationCircleIcon } from '@heroicons/react/outline';

interface AlertProps {
    type: 'success' | 'info' | 'warning' | 'danger' | 'error';
    className?: string;
    children: React.ReactNode;
}

export default ({ type, className, children }: AlertProps) => {
    return (
        <div
            className={classNames(
                'flex items-center border-l-8 text-gray-50 rounded-md shadow px-4 py-3',
                {
                    ['border-green-500 bg-green-500/25']: type === 'success',
                    ['border-blue-500 bg-blue-500/25']: type === 'info',
                    ['border-yellow-500 bg-yellow-500/25']: type === 'warning',
                    ['border-red-500 bg-red-500/25']: type === 'danger',
                    ['border-red-500 bg-red-400/25']: type === 'error',
                },
                className
            )}
        >
            {type === 'warning' ? (
                <ExclamationIcon className={'w-6 h-6 text-yellow-500 mr-2'} />
            ) : type === 'success' ? (
                <InformationCircleIcon className={'w-6 h-6 text-green-500 mr-2'} />
            ) : type === 'info' ? (
                <InformationCircleIcon className={'w-6 h-6 text-blue-500 mr-2'} />
            ) : (
                <InformationCircleIcon className={'w-6 h-6 text-red-500 mr-2'} />
            )}
            {children}
        </div>
    );
};
