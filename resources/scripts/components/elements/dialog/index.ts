import DialogComponent from './Dialog';
import DialogFooter from './DialogFooter';
import DialogIcon from './DialogIcon';
import DialogTrigger from './DialogTrigger';
import ConfirmationDialog from './ConfirmationDialog';
import ConfirmationDialogTrigger from './ConfirmationDialogTrigger';
export { useDialogState } from './useDialogState';

const Dialog = Object.assign(DialogComponent, {
    Confirm: ConfirmationDialog,
    ConfirmTrigger: ConfirmationDialogTrigger,
    Footer: DialogFooter,
    Icon: DialogIcon,
    Trigger: DialogTrigger,
});

export { Dialog };
export * from './types.d';
export * from './context';
