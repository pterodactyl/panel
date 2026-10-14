import type { ReactNode } from 'react';
import NamedIcon from '@/components/elements/NamedIcon';

export interface NavigationLabelProps {
    label: string;
    /** Shown as the label's title. */
    group?: string | null;
    /** A lucide icon name. */
    icon?: string | null;
    /** A navigation item's `state.badge`. */
    badge?: ReactNode;
}

/** A navigation item's icon, label and badge. */
export default function NavigationLabel({ label, group, icon, badge }: NavigationLabelProps) {
    return (
        <span className='inline-flex items-center gap-2 align-top' title={group ?? undefined}>
            {icon && <NamedIcon name={icon} size={16} className='shrink-0' aria-hidden />}
            <span>{label}</span>
            {badge}
        </span>
    );
}
