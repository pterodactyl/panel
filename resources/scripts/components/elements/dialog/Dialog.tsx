import React, { Fragment } from 'react';
import { Dialog as HeadlessDialog, Transition } from '@headlessui/react';
import { Button } from '@/components/elements/button/index';
import styles from './style.module.css';
import { XIcon } from '@heroicons/react/solid';
import { CheckIcon, ExclamationIcon, InformationCircleIcon, ShieldExclamationIcon } from '@heroicons/react/outline';
import classNames from 'classnames';

interface Props {
    visible: boolean;
    onDismissed: () => void;
    title?: string;
    children?: React.ReactNode;
}

interface DialogIconProps {
    type: 'danger' | 'info' | 'success' | 'warning';
    className?: string;
}

const DialogIcon = ({ type, className }: DialogIconProps) => {
    const [ Component, styles ] = (function (): [(props: React.ComponentProps<'svg'>) => JSX.Element, string] {
        switch (type) {
            case 'danger':
                return [ ShieldExclamationIcon, 'bg-red-500 text-red-50' ];
            case 'warning':
                return [ ExclamationIcon, 'bg-yellow-600 text-yellow-50' ];
            case 'success':
                return [ CheckIcon, 'bg-green-600 text-green-50' ];
            case 'info':
                return [ InformationCircleIcon, 'bg-primary-500 text-primary-50' ];
        }
    })();

    return (
        <div className={classNames('flex items-center justify-center w-10 h-10 rounded-full', styles, className)}>
            <Component className={'w-6 h-6'} />
        </div>
    );
};

const DialogButtons = ({ children }: { children: React.ReactNode }) => (
    <>{children}</>
);

const Dialog = ({ visible, title, onDismissed, children }: Props) => {
    const items = React.Children.toArray(children || []);
    const [ buttons, icon, content ] = [
        // @ts-expect-error
        items.find(child => child.type === DialogButtons),
        // @ts-expect-error
        items.find(child => child.type === DialogIcon),
        // @ts-expect-error
        items.filter(child => ![ DialogIcon, DialogButtons ].includes(child.type)),
    ];

    return (
        <Transition show={visible} as={Fragment}>
            <HeadlessDialog onClose={() => onDismissed()} className={styles.wrapper}>
                <div className={'flex items-center justify-center min-h-screen'}>
                    <Transition.Child
                        as={Fragment}
                        enter={'ease-out duration-200'}
                        enterFrom={'opacity-0'}
                        enterTo={'opacity-100'}
                        leave={'ease-in duration-100'}
                        leaveFrom={'opacity-100'}
                        leaveTo={'opacity-0'}
                    >
                        <HeadlessDialog.Overlay className={styles.overlay}/>
                    </Transition.Child>
                    <Transition.Child
                        as={Fragment}
                        enter={'ease-out duration-200'}
                        enterFrom={'opacity-0 scale-95'}
                        enterTo={'opacity-100 scale-100'}
                        leave={'ease-in duration-100'}
                        leaveFrom={'opacity-100 scale-100'}
                        leaveTo={'opacity-0 scale-95'}
                    >
                        <div className={styles.container}>
                            <div className={'flex p-6'}>
                                {icon && <div className={'mr-4'}>{icon}</div>}
                                <div className={'flex-1'}>
                                    {title &&
                                    <HeadlessDialog.Title className={styles.title}>
                                        {title}
                                    </HeadlessDialog.Title>
                                    }
                                    <HeadlessDialog.Description className={'pr-4'}>
                                        {content}
                                    </HeadlessDialog.Description>
                                </div>
                            </div>
                            {buttons && <div className={styles.button_bar}>{buttons}</div>}
                            {/* Keep this below the other buttons so that it isn't the default focus if they're present. */}
                            <div className={'absolute right-0 top-0 m-4'}>
                                <Button.Text square small onClick={() => onDismissed()} className={'hover:rotate-90'}>
                                    <XIcon className={'w-5 h-5'}/>
                                </Button.Text>
                            </div>
                        </div>
                    </Transition.Child>
                </div>
            </HeadlessDialog>
        </Transition>
    );
};

const _Dialog = Object.assign(Dialog, { Buttons: DialogButtons, Icon: DialogIcon });

export default _Dialog;
