import React, { useEffect, useState } from 'react';
import { Dialog as HDialog } from '@headlessui/react';
import { Button } from '@/components/elements/button/index';
import { XIcon } from '@heroicons/react/solid';
import DialogIcon, { IconPosition } from '@/components/elements/dialog/DialogIcon';
import { AnimatePresence, motion, useAnimation } from 'framer-motion';
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
    preventExternalClose?: boolean;
    title?: string;
    description?: string | undefined;
    children?: React.ReactNode;
}

const spring = { type: 'spring', damping: 15, stiffness: 300, duration: 0.15 };
const variants = {
    open: { opacity: 1, scale: 1, transition: spring },
    closed: { opacity: 0, scale: 0.85, transition: spring },
    bounce: {
        scale: 0.95,
        transition: { type: 'linear', duration: 0.075 },
    },
};

const Dialog = ({
    open,
    title,
    description,
    onClose,
    hideCloseIcon,
    preventExternalClose,
    children,
}: FullDialogProps) => {
    const controls = useAnimation();

    const [icon, setIcon] = useState<React.ReactNode>();
    const [footer, setFooter] = useState<React.ReactNode>();
    const [iconPosition, setIconPosition] = useState<IconPosition>('title');

    const onDialogClose = (): void => {
        if (!preventExternalClose) {
            return onClose();
        }

        controls
            .start('bounce')
            .then(() => controls.start('open'))
            .catch(console.error);
    };

    useEffect(() => {
        controls.start(open ? 'open' : 'closed').catch(console.error);
    }, [open]);

    return (
        <AnimatePresence>
            {open && (
                <DialogContext.Provider value={{ setIcon, setFooter, setIconPosition }}>
                    <HDialog
                        static
                        as={motion.div}
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ duration: 0.15 }}
                        open={open}
                        onClose={onDialogClose}
                    >
                        <div className={'fixed inset-0 bg-gray-900/50 z-40'} />
                        <div className={'fixed inset-0 overflow-y-auto z-50'}>
                            <div className={styles.container}>
                                <HDialog.Panel
                                    as={motion.div}
                                    animate={controls}
                                    variants={variants}
                                    className={styles.panel}
                                >
                                    <div className={'flex p-6 overflow-y-auto'}>
                                        {iconPosition === 'container' && icon}
                                        <div className={'flex-1 max-h-[70vh]'}>
                                            <div className={'flex items-center'}>
                                                {iconPosition !== 'container' && icon}
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
                                    {footer}
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
