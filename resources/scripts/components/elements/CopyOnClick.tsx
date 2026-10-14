import React, { useEffect, useState } from 'react';
import Portal from '@/components/elements/Portal';
import Button from '@/components/elements/Button';
import { TextArea, TextInput } from '@/components/form/controls';
import copyToClipboard from '@/lib/clipboard';
import { cn } from '@/lib/cn';
import { useSkin } from '@/themes/skins';

export interface CopyOnClickProps {
    text: string | number | null | undefined;
    showInNotification?: boolean;
    children: React.ReactNode;
}

interface ClickableChildProps {
    className?: string;
    role?: React.AriaRole;
    tabIndex?: number;
    onClick?: React.MouseEventHandler<HTMLElement>;
    onKeyDown?: React.KeyboardEventHandler<HTMLElement>;
}

type CopyStatus = 'copied' | 'failed';

const interactiveTypes = new Set<React.ReactElement['type']>([
    'a',
    'button',
    'input',
    'select',
    'textarea',
    Button,
    Button.Text,
    Button.Danger,
    TextInput,
    TextArea,
]);

const copyStatusMessage = (status: CopyStatus, text: CopyOnClickProps['text'], showInNotification: boolean): string => {
    if (status === 'failed') {
        return 'Unable to copy text to clipboard.';
    }

    return showInNotification ? `Copied "${String(text)}" to clipboard.` : 'Copied text to clipboard.';
};

/** Copies `text` when its single child is clicked; a non-interactive child becomes a focusable button. */
const CopyOnClick = ({ text, showInNotification = false, children }: CopyOnClickProps) => {
    const [status, setStatus] = useState<CopyStatus | null>(null);
    const ui = useSkin('CopyOnClick', { status: status ?? undefined });

    useEffect(() => {
        if (!status) {
            return;
        }

        const timeout = setTimeout(() => {
            setStatus(null);
        }, 2500);

        return () => {
            clearTimeout(timeout);
        };
    }, [status]);

    if (!React.isValidElement<ClickableChildProps>(children)) {
        throw new Error('Component passed to <CopyOnClick/> must be a valid React element.');
    }

    const element = React.Children.only(children);

    const copy = () => {
        void copyToClipboard(String(text)).then((copied) => setStatus(copied ? 'copied' : 'failed'));
    };

    const interactive = interactiveTypes.has(element.type);

    const onKeyDown = (e: React.KeyboardEvent<HTMLElement>) => {
        element.props.onKeyDown?.(e);
        if (!e.defaultPrevented && e.target === e.currentTarget && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            copy();
        }
    };

    const child = text
        ? React.cloneElement(element, {
              role: interactive ? element.props.role : (element.props.role ?? 'button'),
              tabIndex: interactive ? element.props.tabIndex : (element.props.tabIndex ?? 0),
              onKeyDown: interactive ? element.props.onKeyDown : onKeyDown,
              className: cn(element.props.className, ui.classes('target')),
              onClick: (e: React.MouseEvent<HTMLElement>) => {
                  copy();
                  element.props.onClick?.(e);
              },
          })
        : element;

    return (
        <>
            {status && (
                <Portal>
                    <div {...ui('root')}>
                        <div role='status' {...ui('status')}>
                            <p {...ui('message')}>{copyStatusMessage(status, text, showInNotification)}</p>
                        </div>
                    </div>
                </Portal>
            )}
            {child}
        </>
    );
};

export default CopyOnClick;
