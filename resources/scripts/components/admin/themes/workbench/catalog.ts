import { getViewOwners } from '@/extensions/registry';
import { uiPrimitives } from '@/themes/gallery';
import { getViewChain, type ViewDefinition } from '@/views/registry';

export type WorkbenchGroupId = 'layouts' | 'pages' | 'blocks' | 'extensions' | 'ui';

export interface WorkbenchEntry {
    readonly name: string;
    /** Fixture names, or variant names for a primitive. */
    readonly fixtures: readonly string[];
    /** The packages stacking a layer over classic: the theme chain, then extension overrides. */
    readonly overrides: readonly string[];
}

export interface WorkbenchGroup {
    readonly id: WorkbenchGroupId;
    readonly label: string;
    readonly entries: readonly WorkbenchEntry[];
}

export interface WorkbenchFilter {
    readonly query: string;
    /** Only views a theme or extension layers over. */
    readonly overridden: boolean;
}

const labels = {
    layouts: 'Layouts',
    pages: 'Pages',
    blocks: 'Blocks',
    extensions: 'Extension views',
    ui: 'UI kit',
} satisfies Record<WorkbenchGroupId, string>;

function groupOf(definition: ViewDefinition): WorkbenchGroupId {
    if (definition.extensionId) {
        return 'extensions';
    }

    if (definition.kind === 'layout') {
        return 'layouts';
    }

    return definition.kind === 'page' ? 'pages' : 'blocks';
}

/** Every search term appears in the name, so `serv card` finds `dashboard.serverCard`. */
export function matchesQuery(name: string, query: string): boolean {
    const haystack = name.toLowerCase();

    return query
        .toLowerCase()
        .split(/\s+/)
        .filter(Boolean)
        .every((term) => haystack.includes(term));
}

export function viewEntry(definition: ViewDefinition): WorkbenchEntry {
    return {
        name: definition.name,
        fixtures: Object.keys(definition.fixtures ?? {}),
        overrides: [
            ...getViewChain(definition.name)
                .filter((layer) => layer.source === 'theme')
                .map((layer) => layer.id),
            ...getViewOwners(definition.name),
        ],
    };
}

/** The workbench tree: views by kind, then extension-defined views and the UI kit, filtered. */
export function workbenchGroups(definitions: readonly ViewDefinition[], filter: WorkbenchFilter): WorkbenchGroup[] {
    const groups = new Map<WorkbenchGroupId, WorkbenchEntry[]>(
        (['layouts', 'pages', 'blocks', 'extensions', 'ui'] as const).map((id) => [id, []])
    );

    for (const definition of definitions) {
        const entry = viewEntry(definition);

        if (matchesQuery(entry.name, filter.query) && (!filter.overridden || entry.overrides.length > 0)) {
            groups.get(groupOf(definition))?.push(entry);
        }
    }

    if (!filter.overridden) {
        for (const [name, primitive] of Object.entries(uiPrimitives)) {
            if (matchesQuery(name, filter.query)) {
                groups.get('ui')?.push({ name, fixtures: Object.keys(primitive.samples), overrides: [] });
            }
        }
    }

    return [...groups].flatMap(([id, entries]) => (entries.length ? [{ id, label: labels[id], entries }] : []));
}
