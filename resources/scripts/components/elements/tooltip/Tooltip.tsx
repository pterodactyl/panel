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

interface Props {
    content: string | React.ReactChild;
    arrow?: boolean;
    placement?: Placement;
    strategy?: Strategy;
    className?: string;
    children: React.ReactElement;
}

const arrowSides: Record<Side, Side> = {
    top: 'bottom',
    bottom: 'top',
    left: 'right',
    right: 'left',
};

export default ({ content, children, ...props }: Props) => {
    const arrowEl = useRef<HTMLSpanElement>(null);
    const [ open, setOpen ] = useState(false);

    const { x, y, reference, floating, middlewareData, strategy, context } = useFloating({
        open,
        placement: props.placement || 'top',
        strategy: props.strategy || 'absolute',
        middleware: [ offset(6), flip(), shift({ padding: 6 }), arrow({ element: arrowEl, padding: 6 }) ],
        onOpenChange: setOpen,
        whileElementsMounted: autoUpdate,
    });

    const { getReferenceProps, getFloatingProps } = useInteractions([
        useFocus(context),
        useHover(context, { restMs: 30 }),
        useRole(context, { role: 'tooltip' }),
        useDismiss(context),
    ]);

    const side = arrowSides[(props.placement || 'top').split('-')[0] as Side];
    const { x: ax, y: ay } = middlewareData.arrow || {};

    return (
        <>
            {cloneElement(children, getReferenceProps({ ref: reference, ...children.props }))}
            <AnimatePresence>
                {open &&
                    <motion.span
                        initial={{ opacity: 0, scale: 0.85 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ type: 'easeIn', damping: 20, stiffness: 300, duration: 0.1 }}
                        {...getFloatingProps({
                            ref: floating,
                            className: 'absolute top-0 left-0 bg-gray-900 text-sm text-gray-200 px-3 py-2 rounded pointer-events-none',
                            style: {
                                position: strategy,
                                top: `${y || 0}px`,
                                left: `${x || 0}px`,
                            },
                        })}
                    >
                        {content}
                        {props.arrow &&
                            <span
                                ref={arrowEl}
                                style={{
                                    transform: `translate(${Math.round(ax || 0)}px, ${Math.round(ay || 0)}px)`,
                                    [side]: '-6px',
                                }}
                                className={'absolute top-0 left-0 bg-gray-900 w-3 h-3 rotate-45'}
                            />
                        }
                    </motion.span>
                }
            </AnimatePresence>
        </>
    );
};
