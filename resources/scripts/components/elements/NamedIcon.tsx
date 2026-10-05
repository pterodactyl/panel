import { useSyncExternalStore } from 'react';
import {
    ChartNoAxesCombined,
    File,
    KeyRound,
    Network,
    Puzzle,
    Server,
    Settings,
    Terminal,
    Users,
    type LucideIcon,
    type LucideProps,
} from 'lucide-react';
import { cn } from '@/lib/cn';
import type * as IconCatalogModule from './iconCatalog';

// Names that render immediately; every other name waits for the lazily loaded icon catalog.
const bundled = new Map<string, LucideIcon>([
    ['chart-no-axes-combined', ChartNoAxesCombined],
    ['file', File],
    ['key-round', KeyRound],
    ['network', Network],
    ['puzzle', Puzzle],
    ['server', Server],
    ['settings', Settings],
    ['terminal', Terminal],
    ['users', Users],
]);

type IconCatalog = typeof IconCatalogModule;

const listeners = new Set<() => void>();
let catalog: IconCatalog | null | undefined;
let pending: Promise<IconCatalog | null> | undefined;

function loadCatalog(): Promise<IconCatalog | null> {
    pending ??= import('./iconCatalog')
        .catch(() => null)
        .then((loaded) => {
            catalog = loaded;
            listeners.forEach((listener) => listener());
            return loaded;
        });
    return pending;
}

function subscribeCatalog(listener: () => void): () => void {
    listeners.add(listener);
    void loadCatalog();
    return () => {
        listeners.delete(listener);
    };
}
const subscribeNothing = () => () => {};
const getCatalog = () => catalog;

/** Every icon name this panel can render. */
export async function loadIconNames(): Promise<readonly string[]> {
    return (await loadCatalog())?.iconNames ?? [...bundled.keys()];
}

/** The icon for a name: `undefined` while the icon set loads, `null` when the name is unknown. */
export function useNamedIcon(name: string): LucideIcon | null | undefined {
    const immediate = bundled.get(name);
    const loaded = useSyncExternalStore(immediate ? subscribeNothing : subscribeCatalog, getCatalog);
    if (immediate) return immediate;
    return loaded === undefined ? undefined : (loaded?.resolveIcon(name) ?? null);
}

export interface NamedIconProps extends Omit<LucideProps, 'ref' | 'name'> {
    /** A lucide icon name in kebab-case, such as `life-buoy`. */
    name: string;
    /** Rendered when the name is not a lucide icon this panel ships. */
    fallback?: LucideIcon;
}

export default function NamedIcon({ name, fallback = Puzzle, className, size = '1em', ...rest }: NamedIconProps) {
    const Resolved = useNamedIcon(name);
    if (Resolved === undefined) {
        return <span aria-hidden className={cn('inline-block', className)} style={{ width: size, height: size }} />;
    }
    const Component = Resolved ?? fallback;
    return <Component className={cn('inline-block', className)} size={size} {...rest} />;
}
