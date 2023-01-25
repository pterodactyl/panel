import { Menu } from '@headlessui/react';
import classNames from 'classnames';
import type { MouseEvent, ReactNode, Ref } from 'react';
import { forwardRef } from 'react';
import type { NavLinkProps } from 'react-router-dom';
import { NavLink } from 'react-router-dom';

import styles from './style.module.css';

interface Props {
    children: ReactNode | ((opts: { active: boolean; disabled: boolean }) => JSX.Element);
    danger?: boolean;
    disabled?: boolean;
    className?: string;
    icon?: JSX.Element;
    onClick?: (e: MouseEvent) => void;
}

const DropdownItem = forwardRef<HTMLAnchorElement | HTMLButtonElement, Props & Partial<Omit<NavLinkProps, 'children'>>>(
    ({ disabled, danger, className, onClick, children, icon: IconComponent, ...props }, ref) => {
        return (
            <Menu.Item disabled={disabled}>
                {({ disabled, active }) => (
                    <>
                        {'to' in props && props.to !== undefined ? (
                            <NavLink
                                {...props}
                                to={props.to}
                                ref={ref as unknown as Ref<HTMLAnchorElement>}
                                className={classNames(
                                    styles.menu_item,
                                    {
                                        [styles.danger]: danger,
                                        [styles.disabled]: disabled,
                                    },
                                    className,
                                )}
                                onClick={onClick}
                            >
                                {IconComponent}
                                {typeof children === 'function' ? children({ disabled, active }) : children}
                            </NavLink>
                        ) : (
                            <button
                                type="button"
                                ref={ref as unknown as Ref<HTMLButtonElement>}
                                className={classNames(
                                    styles.menu_item,
                                    {
                                        [styles.danger]: danger,
                                        [styles.disabled]: disabled,
                                    },
                                    className,
                                )}
                                onClick={onClick}
                            >
                                {IconComponent}
                                {typeof children === 'function' ? children({ disabled, active }) : children}
                            </button>
                        )}
                    </>
                )}
            </Menu.Item>
        );
    },
);

export { DropdownItem };
