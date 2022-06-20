import React from 'react';
import Icon from '@/components/elements/Icon';
import { IconDefinition } from '@fortawesome/free-solid-svg-icons';
import classNames from 'classnames';
import Tooltip from '@/components/elements/tooltip/Tooltip';

interface StatBlockProps {
    title: string;
    description?: string;
    color?: string | undefined;
    icon: IconDefinition;
    children: React.ReactNode;
}

export default ({ title, icon, color, description, children }: StatBlockProps) => {
    return (
        <Tooltip arrow placement={'top'} disabled={!description} content={description || ''}>
            <div className={'flex items-center space-x-4 bg-gray-600 rounded p-4 shadow-lg'}>
                <div
                    className={classNames(
                        'transition-colors duration-500',
                        'flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-lg shadow-md',
                        color || 'bg-gray-700',
                    )}
                >
                    <Icon
                        icon={icon}
                        className={classNames(
                            'w-6 h-6 m-auto',
                            {
                                'text-gray-100': !color || color === 'bg-gray-700',
                                'text-gray-50': color && color !== 'bg-gray-700',
                            },
                        )}
                    />
                </div>
                <div className={'flex flex-col justify-center overflow-hidden'}>
                    <p className={'font-header leading-tight text-sm text-gray-200'}>{title}</p>
                    <p className={'text-xl font-semibold text-gray-50 truncate'}>
                        {children}
                    </p>
                </div>
            </div>
        </Tooltip>
    );
};
