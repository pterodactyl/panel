import { getSlotComponents, subscribeExtensionRegistry, type SlotName } from './registry';

interface WatchedView {
    readonly view: string;
    /** The theme and extension layers above classic, which decide what the view renders. */
    readonly layers: readonly string[];
    readonly slots: readonly SlotName[];
}

/** How many `<Slot>`s and views of each name are mounted. */
const mounted = new Map<string, number>();
const mountedViewNames = new Map<string, number>();
const watched = new Set<WatchedView>();
const warned = new Set<string>();
let timer: ReturnType<typeof setTimeout> | undefined;
let unsubscribe: (() => void) | undefined;

/** Lazy layers and extension bundles mount after the first render, so the check waits for them. */
export const SLOT_CHECK_DELAY_MS = 3000;

const quoted = (names: readonly string[]): string => names.map((name) => `"${name}"`).join(' and ');

function check(): void {
    timer = undefined;

    for (const { view, layers, slots } of watched) {
        for (const slot of slots) {
            const owners = [...new Set(getSlotComponents(slot).map(({ extensionId }) => extensionId))];
            const key = `${layers.join()}|${view}|${slot}`;

            if (!owners.length || (mounted.get(slot) ?? 0) > 0 || warned.has(key)) {
                continue;
            }

            warned.add(key);
            const subject = layers.length > 1 ? `${quoted(layers)} layers` : `${quoted(layers)} layer`;
            const verb = layers.length > 1 ? 'do' : 'does';

            console.warn(
                `[views] The ${subject} of "${view}" ${verb} not render the "${slot}" slot, so ${quoted(owners)} does not show there.`
            );
        }
    }
}

function schedule(): void {
    if (watched.size) {
        clearTimeout(timer);
        timer = setTimeout(check, SLOT_CHECK_DELAY_MS);
    }
}

function count(counts: Map<string, number>, name: string): () => void {
    counts.set(name, (counts.get(name) ?? 0) + 1);
    schedule();

    return () => {
        counts.set(name, (counts.get(name) ?? 1) - 1);
        schedule();
    };
}

const positive = (counts: Map<string, number>): ReadonlySet<string> =>
    new Set([...counts].filter(([, total]) => total > 0).map(([name]) => name));

/** Counts a mounted `<Slot>`; returns its removal. */
export const slotMounted = (name: string): (() => void) => count(mounted, name);

/** Counts a mounted view; returns its removal. */
export const viewMounted = (name: string): (() => void) => count(mountedViewNames, name);

/** The names of the slots mounted right now. */
export const mountedSlots = (): ReadonlySet<string> => positive(mounted);

/** The names of the views mounted right now. */
export const mountedViews = (): ReadonlySet<string> => positive(mountedViewNames);

/**
 * Warns, once per slot, when a view whose layers above classic leave out one of its slots has components
 * registered there, which would otherwise look like the extension failed. Returns its removal.
 */
export function watchViewSlots(view: string, layers: readonly string[], slots: readonly SlotName[]): () => void {
    const entry: WatchedView = { view, layers, slots };

    watched.add(entry);
    unsubscribe ??= subscribeExtensionRegistry(schedule);
    schedule();

    return () => {
        watched.delete(entry);

        if (!watched.size) {
            unsubscribe?.();
            unsubscribe = undefined;
            clearTimeout(timer);
            timer = undefined;
        }
    };
}
