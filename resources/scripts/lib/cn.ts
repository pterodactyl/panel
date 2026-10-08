import { clsx, type ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

/** The Tailwind prefixes of the enabled extensions (`hw` for `hw:flex`), each extension building with its own. */
const prefixes = new Set<string>();

/*
 * An extension utility is the same utility under another name, so it is parsed without the
 * prefix: `hw:mt-4` passed to a core component replaces that component's `mt-2` instead of
 * leaving both on the element for stylesheet order to settle.
 */
const createMerge = () =>
    extendTailwindMerge({
        experimentalParseClassName: ({ className, parseClassName }) => {
            const end = className.indexOf(':');

            return parseClassName(
                end > 0 && prefixes.has(className.slice(0, end)) ? className.slice(end + 1) : className
            );
        },
    });

let merge = createMerge();

/**
 * Teach `cn` the Tailwind prefixes extensions build with, from the bootstrap payload before
 * the first render. A prefix registered later still applies: tailwind-merge caches what it
 * resolved, so the merge function is rebuilt whenever a new prefix arrives.
 */
export function registerClassPrefixes(names: Iterable<string | null | undefined>): void {
    let added = false;

    for (const name of names) {
        if (name && /^[a-z]+$/.test(name) && !prefixes.has(name)) {
            prefixes.add(name);
            added = true;
        }
    }

    if (added) {
        merge = createMerge();
    }
}

export const cn = (...inputs: ClassValue[]): string => merge(clsx(inputs));
