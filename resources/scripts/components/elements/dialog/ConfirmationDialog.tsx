import React, { useLayoutEffect, useRef } from 'react';
import DialogComponent from './Dialog';
import DialogFooter from './DialogFooter';
import type { RenderDialogProps } from './types';
import Button from '@/components/elements/Button';

type ConfirmationProps = Omit<RenderDialogProps, 'children'> & {
    children?: React.ReactNode;
    confirm?: string | undefined;
    /** Disables both actions, shows a spinner on the confirm button and ignores close requests. */
    pending?: boolean;
    onConfirmed: (e: React.MouseEvent<HTMLButtonElement, MouseEvent>) => void;
};

export default function ConfirmationDialog({
    confirm = 'Okay',
    pending = false,
    children,
    onConfirmed,
    preventExternalClose,
    hideCloseIcon,
    ...props
}: ConfirmationProps) {
    // Blocks a second confirm until the caller has rendered its pending state.
    const confirmed = useRef(false);

    useLayoutEffect(() => {
        if (!pending) {
            confirmed.current = false;
        }
    });

    return (
        <DialogComponent
            {...props}
            preventExternalClose={preventExternalClose || pending}
            hideCloseIcon={hideCloseIcon || pending}
        >
            {children}
            <DialogFooter>
                <Button.Text type='button' disabled={pending} onClick={props.onClose}>
                    Cancel
                </Button.Text>
                <Button.Danger
                    type='button'
                    isLoading={pending}
                    onClick={(event) => {
                        if (props.open && !pending && !confirmed.current) {
                            confirmed.current = true;
                            onConfirmed(event);
                        }
                    }}
                >
                    {confirm}
                </Button.Danger>
            </DialogFooter>
        </DialogComponent>
    );
}
