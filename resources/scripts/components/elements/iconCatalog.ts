import { icons, type LucideIcon } from 'lucide-react';
import names from '../../../../packages/sdk/icons.json';

// Import lazily only: this module pulls the whole lucide icon set into its chunk.
const catalog: Readonly<Record<string, LucideIcon | undefined>> = icons;
const known = new Set<string>(names);
const exportName = (name: string): string =>
    name.replaceAll(/(?:^|-)([a-z0-9])/g, (_match, letter: string) => letter.toUpperCase());

export const iconNames: readonly string[] = Object.freeze([...names]);

export function resolveIcon(name: string): LucideIcon | undefined {
    return known.has(name) ? catalog[exportName(name)] : undefined;
}
