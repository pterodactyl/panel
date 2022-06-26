import React, { ElementType, forwardRef, useMemo } from 'react';
import { Menu, Transition } from '@headlessui/react';
import styles from './style.module.css';
import classNames from 'classnames';
import DropdownItem from '@/components/elements/dropdown/DropdownItem';
import DropdownButton from '@/components/elements/dropdown/DropdownButton';

interface Props {
    as?: ElementType;
    children: React.ReactNode;
}

const DropdownGap = ({ invisible }: { invisible?: boolean }) => (
    <div className={classNames('border m-2', { 'border-neutral-700': !invisible, 'border-transparent': invisible })} />
);

type TypedChild = (React.ReactChild | React.ReactFragment | React.ReactPortal) & {
    type?: JSX.Element;
};

const Dropdown = forwardRef<typeof Menu, Props>(({ as, children }, ref) => {
    const [Button, items] = useMemo(() => {
        const list = React.Children.toArray(children) as unknown as TypedChild[];

        return [
            list.filter((child) => child.type === DropdownButton),
            list.filter((child) => child.type !== DropdownButton),
        ];
    }, [children]);

    if (!Button) {
        throw new Error('Cannot mount <Dropdown /> component without a child <Dropdown.Button />.');
    }

    return (
        <Menu as={as || 'div'} className={styles.menu} ref={ref}>
            {Button}
            <Transition
                enter={'transition duration-100 ease-out'}
                enterFrom={'transition scale-95 opacity-0'}
                enterTo={'transform scale-100 opacity-100'}
                leave={'transition duration-75 ease-out'}
                leaveFrom={'transform scale-100 opacity-100'}
                leaveTo={'transform scale-95 opacity-0'}
            >
                <Menu.Items className={classNames(styles.items_container, 'w-56')}>
                    <div className={'px-1 py-1'}>{items}</div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
});

const _Dropdown = Object.assign(Dropdown, {
    Button: DropdownButton,
    Item: DropdownItem,
    Gap: DropdownGap,
});

export { _Dropdown as default };
