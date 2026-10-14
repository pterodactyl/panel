import type React from 'react';
import { replaceable, useSkin, type UiTone } from '@/themes/skins';

export type BadgeSize = 'sm' | 'md';
/** `solid` fills the badge with its tone; `soft` tints it. */
export type BadgeVariant = 'solid' | 'soft';

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    tone?: UiTone;
    size?: BadgeSize;
    variant?: BadgeVariant;
}

/** A short status or count label. */
function CoreBadge({ tone = 'neutral', size = 'md', variant = 'solid', className, ...props }: BadgeProps) {
    const ui = useSkin('Badge', { tone, size, variant });

    return <span {...ui('root', className)} {...props} />;
}

const Badge = replaceable('Badge', CoreBadge);

export default Badge;
