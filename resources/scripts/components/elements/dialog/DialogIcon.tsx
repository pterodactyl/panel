import React, { useContext, useEffect } from 'react';
import { CheckIcon, ExclamationIcon, InformationCircleIcon, ShieldExclamationIcon } from '@heroicons/react/outline';
import classNames from 'classnames';
import DialogContext from '@/components/elements/dialog/context';
import styles from './style.module.css';

export type IconPosition = 'title' | 'container' | undefined;

interface Props {
    type: 'danger' | 'info' | 'success' | 'warning';
    position?: IconPosition;
    className?: string;
}

const icons = {
    danger: ShieldExclamationIcon,
    warning: ExclamationIcon,
    success: CheckIcon,
    info: InformationCircleIcon,
};

export default ({ type, position, className }: Props) => {
    const { setIcon, setIconPosition } = useContext(DialogContext);

    useEffect(() => {
        const Icon = icons[type];

        setIcon(
            <div className={classNames(styles.dialog_icon, styles[type], className)}>
                <Icon className={'w-6 h-6'} />
            </div>
        );
    }, [type, className]);

    useEffect(() => {
        setIconPosition(position);
    }, [position]);

    return null;
};
