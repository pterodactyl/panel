import type * as React from 'react';
import { Plus, type LucideIcon } from 'lucide-react';
import Button, { RouterLinkButton, type ButtonProps } from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import { useSkin } from '@/themes/skins';

interface IconProps {
    /** Replaces the Plus for sibling toolbar actions such as Upload. */
    icon?: LucideIcon;
}

export type NewButtonProps = ButtonProps & IconProps;

/** The panel's "create" action: a Plus icon and a sentence-case label such as "New node". */
export function NewButton({ icon = Plus, className, children, ...props }: NewButtonProps) {
    const ui = useSkin('NewButton', { link: false });

    return (
        <Button className={ui.classes('root', className)} {...props}>
            <span {...ui('content')}>
                <Icon icon={icon} aria-hidden {...ui('icon')} />
                {children}
            </span>
        </Button>
    );
}

export type NewLinkButtonProps = Omit<React.ComponentProps<typeof RouterLinkButton>, 'children'> &
    IconProps & { children: React.ReactNode };

/** `NewButton` for actions that navigate to a create page. */
export function NewLinkButton({ icon = Plus, className, children, ...props }: NewLinkButtonProps) {
    const ui = useSkin('NewButton', { link: true });

    return (
        <RouterLinkButton className={ui.classes('root', className)} {...props}>
            <Icon icon={icon} aria-hidden {...ui('icon')} />
            {children}
        </RouterLinkButton>
    );
}
