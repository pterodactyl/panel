import React, { forwardRef } from 'react';
import { Menu } from '@headlessui/react';
import styles from './styles.module.css';
import classNames from 'classnames';

interface Props {
    children: React.ReactNode | ((opts: { active: boolean; disabled: boolean }) => JSX.Element);
    danger?: boolean;
    disabled?: boolean;
    className?: string;
    icon?: JSX.Element;
    onClick?: (e: React.MouseEvent) => void;
}

const DropdownItem = forwardRef<HTMLAnchorElement, Props>(({ disabled, danger, className, onClick, children, icon: IconComponent }, ref) => {
    return (
        <Menu.Item disabled={disabled}>
            {(args) => (
                <a
                    ref={ref}
                    href={'#'}
                    className={classNames(styles.menu_item, { [styles.danger]: danger }, className)}
                    onClick={onClick}
                >
                    {IconComponent}
                    {typeof children === 'function' ? children(args) : children}
                </a>
            )}
        </Menu.Item>
    );
});

export default DropdownItem;
