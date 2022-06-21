import React from 'react';
import Icon from '@/components/elements/Icon';
import { IconDefinition } from '@fortawesome/free-solid-svg-icons';
import classNames from 'classnames';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import styles from './style.module.css';

interface StatBlockProps {
    title: string;
    description?: string;
    color?: string | undefined;
    icon: IconDefinition;
    children: React.ReactNode;
    className?: string;
}

export default ({ title, icon, color, description, className, children }: StatBlockProps) => {
    return (
        <Tooltip arrow placement={'top'} disabled={!description} content={description || ''}>
            <div className={classNames(styles.stat_block, 'bg-gray-600', className)}>
                <div className={classNames(styles.status_bar, color || 'bg-gray-700')}/>
                <div className={classNames(styles.icon, color || 'bg-gray-700')}>
                    <Icon
                        icon={icon}
                        className={classNames({
                            'text-gray-100': !color || color === 'bg-gray-700',
                            'text-gray-50': color && color !== 'bg-gray-700',
                        })}
                    />
                </div>
                <div className={'flex flex-col justify-center overflow-hidden'}>
                    <p className={'font-header leading-tight text-xs md:text-sm text-gray-200'}>{title}</p>
                    <p className={'text-base md:text-xl font-semibold text-gray-50 truncate'}>
                        {children}
                    </p>
                </div>
            </div>
        </Tooltip>
    );
};
