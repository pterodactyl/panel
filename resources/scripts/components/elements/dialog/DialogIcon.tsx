import React, { useContext } from 'react';
import { CheckIcon, ExclamationIcon, InformationCircleIcon, ShieldExclamationIcon } from '@heroicons/react/outline';
import classNames from 'classnames';
import DialogContext from '@/components/elements/dialog/context';
import { createPortal } from 'react-dom';
import styles from './style.module.css';

interface Props {
    type: 'danger' | 'info' | 'success' | 'warning';
    className?: string;
}

const icons = {
    danger: ShieldExclamationIcon,
    warning: ExclamationIcon,
    success: CheckIcon,
    info: InformationCircleIcon,
};

export default ({ type, className }: Props) => {
    const { icon } = useContext(DialogContext);

    if (!icon.current) {
        return null;
    }

    const element = (
        <div className={classNames(styles.dialog_icon, styles[type], className)}>
            {React.createElement(icons[type], { className: 'w-6 h-6' })}
        </div>
    );

    return createPortal(element, icon.current);
};
