import React, { useRef, useState } from 'react';
import ConfirmationDialog from './ConfirmationDialog';
import { useDialogState } from './useDialogState';

type ConfirmationDialogProps = React.ComponentProps<typeof ConfirmationDialog>;

type TriggerProps = {
    onClick: () => void;
};

type ConfirmationDialogTriggerProps = Omit<ConfirmationDialogProps, 'open' | 'onClose' | 'onConfirmed'> & {
    /** A returned promise keeps the dialog pending until it settles; rejections are swallowed. */
    onConfirmed: (event: React.MouseEvent<HTMLButtonElement, MouseEvent>, close: () => void) => void | Promise<void>;
    trigger: (props: TriggerProps) => React.ReactNode;
};

export default function ConfirmationDialogTrigger({
    onConfirmed,
    trigger,
    pending,
    ...props
}: ConfirmationDialogTriggerProps) {
    const dialog = useDialogState();
    const confirmingRef = useRef(false);
    const [confirming, setConfirming] = useState(false);

    const onConfirm = (event: React.MouseEvent<HTMLButtonElement, MouseEvent>) => {
        if (confirmingRef.current || pending) {
            return;
        }

        const result = onConfirmed(event, dialog.hide);

        if (!(result instanceof Promise)) {
            return;
        }

        confirmingRef.current = true;
        setConfirming(true);
        result
            .catch(() => {})
            .finally(() => {
                confirmingRef.current = false;
                setConfirming(false);
            });
    };

    return (
        <>
            {trigger({ onClick: dialog.show })}
            <ConfirmationDialog
                {...props}
                pending={pending || confirming}
                open={dialog.open}
                onClose={dialog.hide}
                onConfirmed={onConfirm}
            />
        </>
    );
}
