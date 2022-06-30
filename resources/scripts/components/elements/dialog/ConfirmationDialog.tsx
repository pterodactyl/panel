import React from 'react';
import { Dialog } from '@/components/elements/dialog/index';
import { DialogProps } from '@/components/elements/dialog/Dialog';
import Button from '@/components/elements/Button';

type ConfirmationProps = Omit<DialogProps, 'description' | 'children'> & {
    children: React.ReactNode;
    confirm?: string | undefined;
    onConfirmed: (e: React.MouseEvent<HTMLButtonElement, MouseEvent>) => void;
};

export default ({ confirm = 'Okay', children, onConfirmed, ...props }: ConfirmationProps) => {
    return (
        <Dialog {...props} description={typeof children === 'string' ? children : undefined}>
            {typeof children !== 'string' && children}
            <Dialog.Buttons>
                <Button color={"grey"} onClick={props.onClose}>Cancel</Button>
                <Button color={"red"} onClick={onConfirmed}>{confirm}</Button>
            </Dialog.Buttons>
        </Dialog>
    );
};
