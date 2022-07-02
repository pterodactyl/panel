import React from 'react';
import { Dialog as HDialog } from '@headlessui/react';
import { Button } from '@/components/elements/button/index';
import { XIcon } from '@heroicons/react/solid';
import DialogIcon from '@/components/elements/dialog/DialogIcon';
import { AnimatePresence, motion } from 'framer-motion';
import classNames from 'classnames';
import ConfirmationDialog from '@/components/elements/dialog/ConfirmationDialog';

export interface DialogProps {
    open: boolean;
    onClose: () => void;
    hideCloseIcon?: boolean;
    title?: string;
    description?: string | undefined;
    children?: React.ReactNode;
}

const DialogButtons = ({ children }: { children: React.ReactNode }) => <>{children}</>;

const Dialog = ({ open, title, description, onClose, hideCloseIcon, children }: DialogProps) => {
    const items = React.Children.toArray(children || []);
    const [buttons, icon, content] = [
        // @ts-expect-error not sure how to get this correct
        items.find((child) => child.type === DialogButtons),
        // @ts-expect-error not sure how to get this correct
        items.find((child) => child.type === DialogIcon),
        // @ts-expect-error not sure how to get this correct
        items.filter((child) => ![DialogIcon, DialogButtons].includes(child.type)),
    ];

    return (
        <AnimatePresence>
            {open && (
                <HDialog
                    static
                    as={motion.div}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.15 }}
                    open={open}
                    onClose={onClose}
                >
                    <div className={'fixed inset-0 bg-gray-900/50 z-40'} />
                    <div className={'fixed inset-0 overflow-y-auto z-50'}>
                        <div className={'flex min-h-full items-center justify-center p-4 text-center'}>
                            <HDialog.Panel
                                as={motion.div}
                                initial={{ opacity: 0, scale: 0.85 }}
                                animate={{ opacity: 1, scale: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ type: 'spring', damping: 15, stiffness: 300, duration: 0.15 }}
                                className={classNames([
                                    'relative bg-gray-600 rounded max-w-xl w-full mx-auto shadow-lg text-left',
                                    'ring-4 ring-gray-800 ring-opacity-80',
                                ])}
                            >
                                <div className={'flex p-6 overflow-y-auto'}>
                                    {icon && <div className={'mr-4'}>{icon}</div>}
                                    <div className={'flex-1 max-h-[70vh]'}>
                                        {title && (
                                            <HDialog.Title
                                                className={'font-header text-xl font-medium mb-2 text-gray-50 pr-4'}
                                            >
                                                {title}
                                            </HDialog.Title>
                                        )}
                                        {description && <HDialog.Description>{description}</HDialog.Description>}
                                        {content}
                                    </div>
                                </div>
                                {buttons && (
                                    <div
                                        className={
                                            'px-6 py-3 bg-gray-700 flex items-center justify-end space-x-3 rounded-b'
                                        }
                                    >
                                        {buttons}
                                    </div>
                                )}
                                {/* Keep this below the other buttons so that it isn't the default focus if they're present. */}
                                {!hideCloseIcon && (
                                    <div className={'absolute right-0 top-0 m-4'}>
                                        <Button.Text
                                            size={Button.Sizes.Small}
                                            shape={Button.Shapes.IconSquare}
                                            onClick={onClose}
                                            className={'hover:rotate-90'}
                                        >
                                            <XIcon className={'w-5 h-5'} />
                                        </Button.Text>
                                    </div>
                                )}
                            </HDialog.Panel>
                        </div>
                    </div>
                </HDialog>
            )}
        </AnimatePresence>
    );
};

const _Dialog = Object.assign(Dialog, {
    Confirm: ConfirmationDialog,
    Buttons: DialogButtons,
    Icon: DialogIcon,
});

export default _Dialog;
