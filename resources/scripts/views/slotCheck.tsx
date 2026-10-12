import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import type { ViewDefinition, ViewLayer } from './registry';

/** The slots a view rendered, and the view it renders in. */
interface RenderedSlots {
    readonly names: Set<string>;
    readonly parent: RenderedSlots | null;
}

const SlotRecorder = createContext<RenderedSlots | null>(null);

/** The slots the engine renders around a page view, which the view itself does not render. */
export const EngineSlots = createContext<readonly string[]>([]);

/** Tells the view a slot renders in that it did. */
export function useRenderedSlot(name: string): void {
    const rendered = useContext(SlotRecorder);

    useEffect(() => {
        rendered?.names.add(name);
    }, [rendered, name]);
}

export const SLOT_CHECK_DELAY_MS = 3000;

/** Whether the view, or a view it renders inside, rendered one of `slots`, as an app shell does for a thin layout. */
function renderedAny(view: RenderedSlots | null, slots: readonly string[]): boolean {
    return view !== null && (slots.some((slot) => view.names.has(slot)) || renderedAny(view.parent, slots));
}

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
    const parent = useContext(SlotRecorder);
    const [rendered] = useState<RenderedSlots>(() => ({ names: new Set(), parent }));
    const slots = (definition.slots ?? []).filter((slot) => !engine.includes(slot));
    const owner = import.meta.env.DEV && slots.length > 0 && layer?.source !== 'classic' ? layer?.id : undefined;
    const expected = slots.join(', ');

    useEffect(() => {
        if (owner === undefined) {
            return;
        }

        // Lazy layers and their nested views render after this effect.
        const timer = setTimeout(() => {
            if (!renderedAny(rendered, expected.split(', '))) {
                console.warn(
                    `[views] The "${owner}" layer of "${definition.name}" renders none of its slots (${expected}). ` +
                        'Render them with <Slot name="…"/>, or render <Default/>, so extensions keep their place.'
                );
            }
        }, SLOT_CHECK_DELAY_MS);

        return () => clearTimeout(timer);
    }, [owner, rendered, definition.name, expected]);

    return <SlotRecorder.Provider value={rendered}>{children}</SlotRecorder.Provider>;
}
