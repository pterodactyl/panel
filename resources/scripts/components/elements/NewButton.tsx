import type * as React from 'react';
import { Plus, type LucideIcon } from 'lucide-react';
import Button, { RouterLinkButton } from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import { cn } from '@/lib/cn';

const newButtonClass =
    'inline-flex h-9 shrink-0 items-center justify-center gap-2 px-3 normal-case font-medium tracking-normal';

interface IconProps {
    /** Replaces the Plus for sibling toolbar actions such as Upload. */
    icon?: LucideIcon;
}

/** The panel's "create" action: a Plus icon and a sentence-case label such as "New node". */
export function NewButton({
    icon = Plus,
    className,
    children,
    ...props
}: React.ComponentProps<typeof Button> & IconProps) {
    return (
        <Button className={cn(newButtonClass, className)} {...props}>
            <span className='inline-flex items-center gap-2'>
                <Icon icon={icon} aria-hidden className='h-4 w-4' />
                {children}
            </span>
        </Button>
    );
}

/** `NewButton` for actions that navigate to a create page. */
export function NewLinkButton({
    icon = Plus,
    className,
    children,
    ...props
}: Omit<React.ComponentProps<typeof RouterLinkButton>, 'children'> & IconProps & { children: React.ReactNode }) {
    return (
        <RouterLinkButton className={cn(newButtonClass, className)} {...props}>
            <Icon icon={icon} aria-hidden className='h-4 w-4' />
            {children}
        </RouterLinkButton>
    );
}
