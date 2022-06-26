import React from 'react';
import classNames from 'classnames';
import useFitText from 'use-fit-text';
import styles from './style.module.css';
import Icon from '@/components/elements/Icon';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { IconDefinition } from '@fortawesome/free-solid-svg-icons';

interface StatBlockProps {
    title: string;
    description?: string;
    icon: IconDefinition;
    children: React.ReactNode;
    className?: string;
}

export default ({ title, icon, description, className, children }: StatBlockProps) => {
    const { fontSize, ref } = useFitText({ minFontSize: 8, maxFontSize: 500 });

    return (
        <Tooltip arrow placement={'top'} disabled={!description} content={description || ''}>
            <div className={classNames(styles.stat_block, 'bg-gray-900', className)}>
                <div className={classNames(styles.status_bar, 'bg-gray-700')}/>
                <div className={classNames(styles.icon, 'bg-gray-700')}>
                    <Icon
                        icon={icon}
                        className={classNames({
                            'text-gray-100': 'bg-gray-700',
                            'text-gray-50': 'bg-gray-700',
                        })}
                    />
                </div>
                <div className={'flex flex-col justify-center overflow-hidden w-full'}>
                    <p className={'font-header leading-tight text-xs md:text-sm text-gray-200'}>{title}</p>
                    <div
                        ref={ref}
                        className={'h-[1.75rem] w-full font-semibold text-gray-50 truncate'}
                        style={{ fontSize }}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </Tooltip>
    );
};
