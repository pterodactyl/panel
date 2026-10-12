import React, { useMemo, useState } from 'react';
import { Dialog as BaseDialog } from '@base-ui/react/dialog';
import Button from '@/components/elements/Button';
import { usePortalContainer } from '@/components/elements/Portal';
import { Check, Info, ShieldAlert, TriangleAlert, X } from 'lucide-react';
import { useSkin, type SkinBinding } from '@/themes/skins';
import { DialogContext } from './context';
import DialogFooter from './DialogFooter';
import type { DialogIconProps, IconPosition, RenderDialogProps } from './types';

const focusableSelector =
    ':is(input, select, textarea, button, a[href], [tabindex]):not([disabled]):not([tabindex="-1"])';

const icons = {
    danger: ShieldAlert,
    warning: TriangleAlert,
    success: Check,
    info: Info,
};

function TypeIcon({ ui, icon }: { ui: SkinBinding<'Dialog'>; icon: DialogIconProps }) {
    const Glyph = icons[icon.type];

    return (
        <div {...ui('icon', icon.className, { type: icon.type })}>
            <Glyph {...ui('iconGlyph')} />
        </div>
    );
}

export default function DialogComponent({
    open,
    title,
    description,
    onClose,
    hideCloseIcon,
    preventExternalClose,
    children,
}: RenderDialogProps) {
    const [icon, setIcon] = useState<DialogIconProps>();
    const [iconPosition, setIconPosition] = useState<IconPosition>('title');
    const popupRef = React.useRef<HTMLDivElement>(null);
    const described = !!description;
    const ui = useSkin('Dialog', { described });
    const container = usePortalContainer();

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

    const context = useMemo(() => ({ setIcon, setIconPosition, described }), [setIcon, setIconPosition, described]);

    const items = React.Children.toArray(children);
    const isFooter = (child: React.ReactNode) => React.isValidElement(child) && child.type === DialogFooter;
    const footer = items.filter(isFooter);
    const content = items.filter((child) => !isFooter(child));
    const typeIcon = icon && <TypeIcon ui={ui} icon={icon} />;

    return (
        <DialogContext.Provider value={context}>
            {/* Base UI unmounts the popup after its data-ending-style transition finishes. */}
            <BaseDialog.Root open={open} onOpenChange={onOpenChange}>
                <BaseDialog.Portal container={container}>
                    <BaseDialog.Backdrop {...ui('backdrop')} />
                    <div {...ui('viewport')}>
                        <div {...ui('container')}>
                            <BaseDialog.Popup ref={popupRef} {...ui('root')} initialFocus={initialFocus}>
                                <div {...ui('body')}>
                                    {iconPosition === 'container' && typeIcon}
                                    <div {...ui('content')}>
                                        <div {...ui('header')}>
                                            {iconPosition !== 'container' && typeIcon}
                                            <div {...ui('heading')}>
                                                {title && <BaseDialog.Title {...ui('title')}>{title}</BaseDialog.Title>}
                                                {description && (
                                                    <BaseDialog.Description {...ui('description')}>
                                                        {description}
                                                    </BaseDialog.Description>
                                                )}
                                            </div>
                                        </div>
                                        {content}
                                        <div {...ui('spacer')} />
                                    </div>
                                </div>
                                {footer}
                                {/* Rendered after the other buttons so it is not the default focus. */}
                                {!hideCloseIcon && (
                                    <div {...ui('close')}>
                                        <Button.Text
                                            type='button'
                                            size='xsmall'
                                            onClick={onClose}
                                            className={ui.classes('closeButton')}
                                        >
                                            <X {...ui('closeIcon')} />
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
