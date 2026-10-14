import type { DOMAttributes, ReactElement, ReactNode, RefAttributes } from 'react';
import PanelTooltip from '@/components/elements/tooltip/Tooltip';

export interface TooltipProps {
    content: ReactNode;
    children: ReactElement<DOMAttributes<Element> & RefAttributes<Element> & { className?: string }>;
    disabled?: boolean;
    arrow?: boolean;
    className?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
    /** What opens the tooltip; hover and focus by default. */
    interactions?: ('hover' | 'focus' | 'click')[];
    /** Milliseconds the pointer rests before the tooltip opens. */
    delay?: number;
}
export function Tooltip(props: TooltipProps): ReactElement {
    return <PanelTooltip {...props} />;
}
