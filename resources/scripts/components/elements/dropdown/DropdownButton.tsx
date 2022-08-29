import classNames from 'classnames';
import styles from '@/components/elements/dropdown/style.module.css';
import { ChevronDownIcon } from '@heroicons/react/solid';
import { Menu } from '@headlessui/react';
import React from 'react';

interface Props {
    className?: string;
    animate?: boolean;
    children: React.ReactNode;
}

export default ({ className, animate = true, children }: Props) => (
    <Menu.Button className={classNames(styles.button, className || 'px-4')}>
        {typeof children === 'string' ? (
            <>
                <span className={'mr-2'}>{children}</span>
                <ChevronDownIcon aria-hidden={'true'} data-animated={animate.toString()} />
            </>
        ) : (
            children
        )}
    </Menu.Button>
);
