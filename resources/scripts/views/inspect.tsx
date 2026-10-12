import { createContext, useContext, useLayoutEffect, useState, type ReactNode } from 'react';
import type { LooseViewInput, ViewDefinition, ViewLayer } from './registry';

/** What a view last rendered with, for the inspector and the workbench. */
export interface InspectedView {
    readonly definition: ViewDefinition;
    readonly model: unknown;
    readonly inputs: LooseViewInput;
    /** Bottom first: classic or the defining extension, the theme chain, then extension overrides. */
    readonly layers: readonly ViewLayer[];
    /** Layers that failed, so the layer below them rendered instead. */
    readonly failed: readonly string[];
}

export interface ViewProbe {
    current(): InspectedView;
    subscribe(listener: () => void): () => void;
}

interface MutableProbe extends ViewProbe {
    update(view: Omit<InspectedView, 'failed'>): void;
    fail(layerId: string): () => void;
}

export interface ViewInspection {
    /** Render `data-view` wrappers (`display: contents`) the inspector outlines. */
    readonly wrap: boolean;
    /** Receives each view as it mounts and returns its cleanup. */
    readonly collect?: (probe: ViewProbe) => () => void;
}

/** Set only while something inspects views; without it views render no wrappers and keep no records. */
export const ViewInspectionContext = createContext<ViewInspection | null>(null);
const ProbeContext = createContext<MutableProbe | null>(null);
const probes = new WeakMap<Element, ViewProbe>();

function createProbe(initial: Omit<InspectedView, 'failed'>): MutableProbe {
    const listeners = new Set<() => void>();
    const failed = new Set<string>();
    let view = initial;
    let snapshot: InspectedView | undefined;
    const publish = () => {
        snapshot = undefined;
        for (const listener of listeners) {
            listener();
        }
    };

    return {
        current: () => (snapshot ??= Object.freeze({ ...view, failed: [...failed] })),
        subscribe(listener) {
            listeners.add(listener);

            return () => {
                listeners.delete(listener);
            };
        },
        update(next) {
            if (next.model !== view.model || next.inputs !== view.inputs || next.layers !== view.layers) {
                view = next;
                publish();
            }
        },
        fail(layerId) {
            failed.add(layerId);
            publish();

            return () => {
                failed.delete(layerId);
                publish();
            };
        },
    };
}

/** The view whose `data-view` wrapper this is. */
export function getViewProbe(element: Element): ViewProbe | undefined {
    return probes.get(element);
}

export function InspectedViewBoundary({
    inspection,
    children,
    ...view
}: Omit<InspectedView, 'failed'> & { inspection: ViewInspection; children?: ReactNode }) {
    const [probe] = useState(() => createProbe(view));
    const { definition, model, inputs, layers } = view;

    useLayoutEffect(
        () => probe.update({ definition, model, inputs, layers }),
        [probe, definition, model, inputs, layers]
    );
    useLayoutEffect(() => inspection.collect?.(probe), [inspection, probe]);

    const content = <ProbeContext.Provider value={probe}>{children}</ProbeContext.Provider>;

    if (!inspection.wrap) {
        return content;
    }

    return (
        <div
            data-view={definition.name}
            style={{ display: 'contents' }}
            ref={(element) => {
                if (element) {
                    probes.set(element, probe);
                }
            }}
        >
            {content}
        </div>
    );
}

/** Rendered in place of a layer that failed, so inspection shows which layer rendered. */
export function FailedLayerMark({ layerId }: { layerId: string }) {
    const probe = useContext(ProbeContext);

    useLayoutEffect(() => probe?.fail(layerId), [probe, layerId]);

    return null;
}
