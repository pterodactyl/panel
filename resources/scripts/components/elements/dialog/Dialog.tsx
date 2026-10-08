import React, { useMemo, useState } from 'react';
import { Dialog as BaseDialog } from '@base-ui/react/dialog';
import Button from '@/components/elements/Button';
import { X } from 'lucide-react';
import { DialogContext } from './context';
import DialogFooter from './DialogFooter';
import type { IconPosition, RenderDialogProps } from './types';
import { dialogTitleClass } from '@/components/ui/typography';
import { cn } from '@/lib/cn';

const focusableSelector =
    ':is(input, select, textarea, button, a[href], [tabindex]):not([disabled]):not([tabindex="-1"])';
const dialogContainerClass = 'flex min-h-full items-center justify-center p-4 text-center';
const closeIconClass = 'w-5 h-5 group-hover:rotate-90 transition-transform duration-100';
const backdropClass = [
    'fixed inset-0 bg-background/50 z-40 transition-opacity duration-150 ease-out',
    'data-[starting-style]:opacity-0 data-[ending-style]:opacity-0 data-[ending-style]:ease-in',
].join(' ');
const panelClass = [
    'relative flex flex-col overflow-hidden bg-card rounded-sm max-w-xl w-full mx-auto shadow-lg text-left',
    'max-h-[calc(100dvh-2rem)] ring-4 ring-border/80 transition duration-150 ease-out',
    'data-[starting-style]:opacity-0 data-[starting-style]:scale-75',
    'data-[ending-style]:opacity-0 data-[ending-style]:scale-75 data-[ending-style]:ease-in',
].join(' ');

export default function DialogComponent({
    open,
    title,
    description,
    onClose,
    hideCloseIcon,
    preventExternalClose,
    children,
}: RenderDialogProps) {
    const [icon, setIcon] = useState<React.ReactNode>();
    const [iconPosition, setIconPosition] = useState<IconPosition>('title');
    const popupRef = React.useRef<HTMLDivElement>(null);

    const initialFocus = (interactionType: string) => {
        if (interactionType === 'touch') {
            return popupRef.current;
        }

        const first = popupRef.current?.querySelector<HTMLElement>(focusableSelector);

        return first?.getAttribute('role') === 'combobox' ? popupRef.current : true;
    };

    // `preventExternalClose` ignores Base UI dismissals; the close button still calls `onClose`.
    const onOpenChange = (nextOpen: boolean): void => {
        if (nextOpen || preventExternalClose) {
            return;
        }

        onClose();
    };

    const context = useMemo(() => ({ setIcon, setIconPosition }), [setIcon, setIconPosition]);

    const items = React.Children.toArray(children);
    const isFooter = (child: React.ReactNode) => React.isValidElement(child) && child.type === DialogFooter;
    const footer = items.filter(isFooter);
    const content = items.filter((child) => !isFooter(child));

    return (
        <DialogContext.Provider value={context}>
            {/* Base UI unmounts the popup after its data-ending-style transition finishes. */}
            <BaseDialog.Root open={open} onOpenChange={onOpenChange}>
                <BaseDialog.Portal>
                    <BaseDialog.Backdrop className={backdropClass} />
                    <div className='fixed inset-0 overflow-y-auto z-50'>
                        <div className={dialogContainerClass}>
                            <BaseDialog.Popup ref={popupRef} className={panelClass} initialFocus={initialFocus}>
                                <div className='flex min-h-0 flex-1 overflow-y-auto p-6 pb-0'>
                                    {iconPosition === 'container' && icon}
                                    <div className='flex-1 min-w-0'>
                                        <div className='flex items-center'>
                                            {iconPosition !== 'container' && icon}
                                            <div>
                                                {title && (
                                                    <BaseDialog.Title
                                                        className={cn(
                                                            dialogTitleClass,
                                                            description ? 'mb-2' : 'mb-5',
                                                            'pr-4'
                                                        )}
                                                    >
                                                        {title}
                                                    </BaseDialog.Title>
                                                )}
                                                {description && (
                                                    <BaseDialog.Description className='mb-5 text-sm leading-relaxed text-muted-foreground'>
                                                        {description}
                                                    </BaseDialog.Description>
                                                )}
                                            </div>
                                        </div>
                                        {content}
                                        <div className='invisible h-6' />
                                    </div>
                                </div>
                                {footer}
                                {/* Rendered after the other buttons so it is not the default focus. */}
                                {!hideCloseIcon && (
                                    <div className='absolute right-0 top-0 m-4'>
                                        <Button.Text
                                            type='button'
                                            size='xsmall'
                                            onClick={onClose}
                                            className='group w-8 h-8 p-0 inline-flex items-center justify-center'
                                        >
                                            <X className={closeIconClass} />
                                        </Button.Text>
                                    </div>
                                )}
                            </BaseDialog.Popup>
                        </div>
                    </div>
                </BaseDialog.Portal>
            </BaseDialog.Root>
        </DialogContext.Provider>
    );
}
