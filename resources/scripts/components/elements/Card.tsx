import type React from 'react';
import type { LucideIcon } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { isString } from '@/lib/objects';
import { replaceable, useSkin } from '@/themes/skins';

export interface CardProps {
    /** A string renders as the card's heading; any other node replaces it. */
    title?: React.ReactNode;
    /** Shown before a string title. */
    icon?: LucideIcon;
    /** Controls placed at the end of the header. */
    actions?: React.ReactNode;
    /** Covers the card with a spinner. */
    loading?: boolean;
    className?: string;
    /** Classes for the body of a card with a header. */
    bodyClassName?: string;
    children?: React.ReactNode;
}

/** A surface for a section of content; with a title or actions it has a header above its body. */
function CoreCard({ title, icon, actions, loading = false, className, bodyClassName, children }: CardProps) {
    const titled = !!title || !!actions;
    const ui = useSkin('Card', { titled, actions: !!actions, loading });

    return (
        <div {...ui('root', className)}>
            {loading && <SpinnerOverlay visible />}
            {titled ? (
                <>
                    <div {...ui('header')}>
                        {isString(title) ? (
                            <h2 {...ui('title')}>
                                {icon && <Icon icon={icon} {...ui('icon')} />}
                                {title}
                            </h2>
                        ) : (
                            title
                        )}
                        {!!actions && <div {...ui('actions')}>{actions}</div>}
                    </div>
                    <div {...ui('body', bodyClassName)}>{children}</div>
                </>
            ) : (
                children
            )}
        </div>
    );
}

const Card = replaceable('Card', CoreCard);

export default Card;
