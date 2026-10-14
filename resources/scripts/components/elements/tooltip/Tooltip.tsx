import React, { cloneElement, useRef, useState } from 'react';
import type { FloatingContext, Placement, Side } from '@floating-ui/react';
import {
    arrow,
    autoUpdate,
    flip,
    FloatingPortal,
    offset,
    shift,
    useClick,
    useDismiss,
    useFloating,
    useFocus,
    useHover,
    useInteractions,
    useRole,
    useMergeRefs,
    useTransitionStyles,
} from '@floating-ui/react';
import { useSkin, type SkinBinding } from '@/themes/skins';

type Interaction = 'hover' | 'click' | 'focus';
type TooltipChildProps = React.DOMAttributes<Element> &
    React.RefAttributes<Element> & {
        className?: string;
    };

export interface TooltipProps {
    rest?: number;
    delay?: number | Partial<{ open: number; close: number }>;
    content: string | React.ReactNode;
    disabled?: boolean;
    arrow?: boolean;
    interactions?: Interaction[];
    placement?: Placement;
    className?: string;
    children: React.ReactElement<TooltipChildProps>;
}

type TooltipInteractionOptions = Pick<TooltipProps, 'interactions' | 'rest' | 'delay'>;

function useTooltipInteractions(
    context: FloatingContext,
    enabled: boolean,
    { interactions = ['hover', 'focus'], rest = 30, delay = 0 }: TooltipInteractionOptions
) {
    return useInteractions([
        useHover(context, {
            restMs: rest,
            delay,
            mouseOnly: true,
            enabled: enabled && interactions.includes('hover'),
        }),
        useFocus(context, { enabled: enabled && interactions.includes('focus') }),
        useClick(context, { enabled: enabled && interactions.includes('click') }),
        useRole(context, { role: 'tooltip', enabled }),
        useDismiss(context, { enabled }),
    ]);
}

type TooltipArrowProps = {
    ref: React.Ref<HTMLDivElement>;
    ui: SkinBinding<'Tooltip'>;
    position?: { x?: number; y?: number };
};

function TooltipArrow({ ref, ui, position }: TooltipArrowProps) {
    const x = Math.round(position?.x || 0);
    const y = Math.round(position?.y || 0);

    return <div ref={ref} style={{ transform: `translate(${x}px, ${y}px) rotate(45deg)` }} {...ui('arrow')} />;
}

export default function Tooltip({ children, ...props }: TooltipProps) {
    const arrowEl = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const enabled = !props.disabled;

    if (!enabled && open) {
        setOpen(false);
    }

    const { refs, floatingStyles, middlewareData, context, placement } = useFloating({
        open: open && enabled,
        strategy: 'fixed',
        // Positions with top/left; the transition below owns the transform.
        transform: false,
        placement: props.placement || 'top',
        middleware: [
            offset(props.arrow ? 10 : 6),
            flip(),
            shift({ padding: 6 }),
            arrow({ element: arrowEl, padding: 6 }),
        ],
        onOpenChange: setOpen,
        whileElementsMounted: autoUpdate,
    });

    const { getReferenceProps, getFloatingProps } = useTooltipInteractions(context, enabled, props);

    const { isMounted, styles: transitionStyles } = useTransitionStyles(context, {
        duration: { open: 100, close: 75 },
        initial: { opacity: 0, transform: 'scale(0.85)' },
        open: { opacity: 1, transform: 'scale(1)' },
        close: { opacity: 0 },
    });

    const referenceRef = useMergeRefs([refs.setReference, children.props.ref]);
    const ui = useSkin('Tooltip', { side: placement.split('-')[0] as Side, arrow: !!props.arrow });

    return (
        <>
            {cloneElement(children, getReferenceProps({ ...children.props, ref: referenceRef }))}
            {isMounted && (
                <FloatingPortal>
                    <div
                        {...getFloatingProps({
                            ref: refs.setFloating,
                            ...ui('root', props.className),
                            style: { ...floatingStyles, ...transitionStyles },
                        })}
                    >
                        {props.content}
                        {props.arrow && <TooltipArrow ref={arrowEl} ui={ui} position={middlewareData.arrow} />}
                    </div>
                </FloatingPortal>
            )}
        </>
    );
}
