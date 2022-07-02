import React, { useRef } from 'react';
import { Dialog as HDialog } from '@headlessui/react';
import { Button } from '@/components/elements/button/index';
import { XIcon } from '@heroicons/react/solid';
import DialogIcon from '@/components/elements/dialog/DialogIcon';
import { AnimatePresence, motion } from 'framer-motion';
import ConfirmationDialog from '@/components/elements/dialog/ConfirmationDialog';
import DialogContext from './context';
import DialogFooter from '@/components/elements/dialog/DialogFooter';
import styles from './style.module.css';

export interface DialogProps {
    open: boolean;
    onClose: () => void;
}

export interface FullDialogProps extends DialogProps {
    hideCloseIcon?: boolean;
    title?: string;
    description?: string | undefined;
    children?: React.ReactNode;
}

const Dialog = ({ open, title, description, onClose, hideCloseIcon, children }: FullDialogProps) => {
    const ref = useRef<HTMLDivElement>(null);
    const icon = useRef<HTMLDivElement>(null);
    const buttons = useRef<HTMLDivElement>(null);

    return (
        <AnimatePresence>
            {open && (
                <DialogContext.Provider value={{ icon, buttons }}>
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
                            <div className={styles.container}>
                                <HDialog.Panel
                                    as={motion.div}
                                    initial={{ opacity: 0, scale: 0.85 }}
                                    animate={{ opacity: 1, scale: 1 }}
                                    exit={{ opacity: 0 }}
                                    transition={{ type: 'spring', damping: 15, stiffness: 300, duration: 0.15 }}
                                    className={styles.panel}
                                >
                                    <div className={'flex p-6 overflow-y-auto'}>
                                        <div ref={ref} className={'flex-1 max-h-[70vh]'}>
                                            <div className={'flex items-center'}>
                                                <div ref={icon} />
                                                <div>
                                                    {title && (
                                                        <HDialog.Title className={styles.title}>{title}</HDialog.Title>
                                                    )}
                                                    {description && (
                                                        <HDialog.Description>{description}</HDialog.Description>
                                                    )}
                                                </div>
                                            </div>
                                            {children}
                                        </div>
                                    </div>
                                    <div ref={buttons} />
                                    {/* Keep this below the other buttons so that it isn't the default focus if they're present. */}
                                    {!hideCloseIcon && (
                                        <div className={'absolute right-0 top-0 m-4'}>
                                            <Button.Text
                                                size={Button.Sizes.Small}
                                                shape={Button.Shapes.IconSquare}
                                                onClick={onClose}
                                                className={'group'}
                                            >
                                                <XIcon className={styles.close_icon} />
                                            </Button.Text>
                                        </div>
                                    )}
                                </HDialog.Panel>
                            </div>
                        </div>
                    </HDialog>
                </DialogContext.Provider>
            )}
        </AnimatePresence>
    );
};

const _Dialog = Object.assign(Dialog, {
    Confirm: ConfirmationDialog,
    Footer: DialogFooter,
    Icon: DialogIcon,
});

export default _Dialog;
