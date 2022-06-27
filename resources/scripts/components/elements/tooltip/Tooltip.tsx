import React, { cloneElement, useRef, useState } from 'react';
import {
    arrow,
    autoUpdate,
    flip,
    offset,
    Placement,
    shift,
    Side,
    Strategy,
    useDismiss,
    useFloating,
    useFocus,
    useHover,
    useInteractions,
    useRole,
} from '@floating-ui/react-dom-interactions';
import { AnimatePresence, motion } from 'framer-motion';
import classNames from 'classnames';

interface Props {
    rest?: number;
    delay?: number | Partial<{ open: number; close: number }>;
    alwaysOpen?: boolean;
    content: string | React.ReactChild;
    disabled?: boolean;
    arrow?: boolean;
    placement?: Placement;
    strategy?: Strategy;
    className?: string;
    children: React.ReactElement;
}

const arrowSides: Record<Side, string> = {
    top: 'bottom-[-6px] left-0',
    bottom: 'top-[-6px] left-0',
    right: 'top-0 left-[-6px]',
    left: 'top-0 right-[-6px]',
};

export default ({ content, children, disabled = false, alwaysOpen = false, delay = 0, rest = 30, ...props }: Props) => {
    const arrowEl = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(alwaysOpen || false);

    const { x, y, reference, floating, middlewareData, strategy, context } = useFloating({
        open,
        placement: props.placement || 'top',
        strategy: props.strategy || 'absolute',
        middleware: [
            offset(props.arrow ? 10 : 6),
            flip(),
            shift({ padding: 6 }),
            arrow({ element: arrowEl, padding: 6 }),
        ],
        onOpenChange: (o) => setOpen(o || alwaysOpen || false),
        whileElementsMounted: autoUpdate,
    });

    const { getReferenceProps, getFloatingProps } = useInteractions([
        useFocus(context),
        useHover(context, { restMs: rest, delay }),
        useRole(context, { role: 'tooltip' }),
        useDismiss(context),
    ]);

    const side = arrowSides[(props.placement || 'top').split('-')[0] as Side];
    const { x: ax, y: ay } = middlewareData.arrow || {};

    if (disabled) {
        return children;
    }

    return (
        <>
            {cloneElement(children, getReferenceProps({ ref: reference, ...children.props }))}
            <AnimatePresence>
                {open && (
                    <motion.div
                        initial={{ opacity: 0, scale: 0.85 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ type: 'spring', damping: 20, stiffness: 300, duration: 0.075 }}
                        {...getFloatingProps({
                            ref: floating,
                            className:
                                'absolute top-0 left-0 bg-gray-900 text-sm text-gray-200 px-3 py-2 rounded pointer-events-none max-w-[20rem] z-[9999]',
                            style: {
                                position: strategy,
                                top: `${y || 0}px`,
                                left: `${x || 0}px`,
                            },
                        })}
                    >
                        {content}
                        {props.arrow && (
                            <div
                                ref={arrowEl}
                                style={{
                                    transform: `translate(${Math.round(ax || 0)}px, ${Math.round(
                                        ay || 0
                                    )}px) rotate(45deg)`,
                                }}
                                className={classNames('absolute bg-gray-900 w-3 h-3', side)}
                            />
                        )}
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
};
