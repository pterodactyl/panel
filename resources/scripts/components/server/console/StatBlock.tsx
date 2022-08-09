import React from 'react';
import classNames from 'classnames';
import useFitText from 'use-fit-text';
import styles from './style.module.css';
import Icon from '@/components/elements/Icon';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { IconDefinition } from '@fortawesome/free-solid-svg-icons';

interface Props {
    title?: string | undefined;
    copyOnClick?: string;
    color?: string | undefined;
    icon: IconDefinition;
    children: React.ReactNode;
    className?: string;
}

export default ({ title, copyOnClick, icon, color, className, children }: Props) => {
    const { fontSize, ref } = useFitText({ minFontSize: 8, maxFontSize: 500 });

    return (
        <CopyOnClick text={copyOnClick}>
            <div className={classNames(styles.stat_block, className)}>
                <div className={classNames(styles.status_bar, color || 'bg-gray-700')} />
                <div className={classNames(styles.icon, color || 'bg-gray-700')}>
                    <Icon
                        icon={icon}
                        className={classNames({
                            'text-gray-100': 'bg-gray-700',
                            'text-gray-50': 'bg-gray-700',
                        })}
                    />
                </div>
                <div className={'flex flex-col justify-center overflow-hidden w-full'}>
                    {title && <p className={'font-header leading-tight text-xs md:text-sm text-gray-200'}>{title}</p>}
                    <div
                        ref={ref}
                        className={'h-[2rem] w-full font-semibold text-gray-50 truncate'}
                        style={{ fontSize }}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </CopyOnClick>
    );
};
