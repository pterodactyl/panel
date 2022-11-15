import React, { useContext, useEffect } from 'react';
import {
    CheckIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    ShieldExclamationIcon,
} from '@heroicons/react/24/outline';
import classNames from 'classnames';
import { DialogContext, DialogIconProps, styles } from './';

const icons = {
    danger: ShieldExclamationIcon,
    warning: ExclamationCircleIcon,
    success: CheckIcon,
    info: InformationCircleIcon,
};

export default ({ type, position, className }: DialogIconProps) => {
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
