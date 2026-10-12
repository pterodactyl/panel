import { createContext, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import type { ViewDefinition, ViewLayer } from './registry';

const SlotRecorder = createContext<((name: string) => void) | null>(null);

/** The slots the engine renders around a page view, which the view itself does not render. */
export const EngineSlots = createContext<readonly string[]>([]);

/** Tells the view a slot renders in that it did. */
export function useRenderedSlot(name: string): void {
    const record = useContext(SlotRecorder);

    useEffect(() => record?.(name), [record, name]);
}

export const SLOT_CHECK_DELAY_MS = 3000;

/**
 * In development, warns when a theme or extension layer renders none of the slots its view renders,
 * which hides whatever extensions mount there. Rendering `Default` renders the slots of the layers below.
 */
export function SlotCheck({
    definition,
    layer,
    children,
}: {
    definition: ViewDefinition;
    layer: ViewLayer | undefined;
    children?: ReactNode;
}) {
    const engine = useContext(EngineSlots);
    const rendered = useRef(false);
    const [record] = useState(() => () => {
        rendered.current = true;
    });
    const slots = (definition.slots ?? []).filter((slot) => !engine.includes(slot));
    const owner = import.meta.env.DEV && slots.length > 0 && layer?.source !== 'classic' ? layer?.id : undefined;
    const expected = slots.join(', ');

    useEffect(() => {
        if (owner === undefined) {
            return;
        }

        // Lazy layers and their nested views render after this effect.
        const timer = setTimeout(() => {
            if (!rendered.current) {
                console.warn(
                    `[views] The "${owner}" layer of "${definition.name}" renders none of its slots (${expected}). ` +
                        'Render them with <Slot name="…"/>, or render <Default/>, so extensions keep their place.'
                );
            }
        }, SLOT_CHECK_DELAY_MS);

        return () => clearTimeout(timer);
    }, [owner, definition.name, expected]);

    return <SlotRecorder.Provider value={owner === undefined ? null : record}>{children}</SlotRecorder.Provider>;
}
