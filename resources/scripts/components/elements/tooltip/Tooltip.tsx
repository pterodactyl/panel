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
import { cn } from '@/lib/cn';

type Interaction = 'hover' | 'click' | 'focus';
type TooltipChildProps = React.DOMAttributes<Element> &
    React.RefAttributes<Element> & {
        className?: string;
    };

interface Props {
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

const arrowSides = {
    top: 'bottom-[-6px] left-0',
    bottom: 'top-[-6px] left-0',
    right: 'top-0 left-[-6px]',
    left: 'top-0 right-[-6px]',
} satisfies Record<Side, string>;

type TooltipInteractionOptions = Pick<Props, 'interactions' | 'rest' | 'delay'>;

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
    placement: Placement;
    position?: { x?: number; y?: number };
};

function TooltipArrow({ ref, placement, position }: TooltipArrowProps) {
    const side = arrowSides[placement.split('-')[0] as Side];
    const x = Math.round(position?.x || 0);
    const y = Math.round(position?.y || 0);

    return (
        <div
            ref={ref}
            style={{ transform: `translate(${x}px, ${y}px) rotate(45deg)` }}
            className={cn('absolute bg-popover w-3 h-3', side)}
        />
    );
}

export default function Tooltip({ children, ...props }: Props) {
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

    return (
        <>
            {cloneElement(children, getReferenceProps({ ...children.props, ref: referenceRef }))}
            {isMounted && (
                <FloatingPortal>
                    <div
                        {...getFloatingProps({
                            ref: refs.setFloating,
                            className: cn(
                                'bg-popover text-sm text-popover-foreground px-3 py-2 rounded-sm pointer-events-none max-w-[24rem] z-9999',
                                props.className
                            ),
                            style: { ...floatingStyles, ...transitionStyles },
                        })}
                    >
                        {props.content}
                        {props.arrow && (
                            <TooltipArrow ref={arrowEl} placement={placement} position={middlewareData.arrow} />
                        )}
                    </div>
                </FloatingPortal>
            )}
        </>
    );
}
