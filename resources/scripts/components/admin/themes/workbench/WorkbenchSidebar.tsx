import { useEffect, useRef, useState } from 'react';
import { Link } from '@tanstack/react-router';
import { Search } from 'lucide-react';
import { cn } from '@/lib/cn';
import type { WorkbenchSearch } from '@/router/search';
import { useViewDefinitions } from '@/views/registry';
import { workbenchGroups, type WorkbenchEntry, type WorkbenchGroupId } from './catalog';

function EntryLink({
    group,
    entry,
    search,
    active,
}: {
    group: WorkbenchGroupId;
    entry: WorkbenchEntry;
    search: WorkbenchSearch;
    active: boolean;
}) {
    const target =
        group === 'ui'
            ? { ...search, view: undefined, ui: entry.name, fixture: undefined }
            : { ...search, ui: undefined, view: entry.name, fixture: undefined };

    return (
        <Link
            to='/panel/theme-workbench'
            search={target}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex items-center gap-2 rounded-sm px-2 py-1 font-mono text-xs no-underline transition-colors',
                active ? 'bg-primary/15 text-foreground' : 'text-foreground/80 hover:bg-popover hover:text-foreground',
                entry.fixtures.length === 0 && !active && 'text-muted-foreground'
            )}
        >
            <span className='min-w-0 flex-1 truncate' title={entry.name}>
                {entry.name}
            </span>
            {entry.overrides.length > 0 && (
                <span
                    className='h-1.5 w-1.5 shrink-0 rounded-full bg-accent'
                    title={`Overridden by ${entry.overrides.join(', ')}`}
                />
            )}
            <span
                className='shrink-0 tabular-nums text-muted-foreground'
                title={entry.fixtures.length ? `${entry.fixtures.length} fixtures` : 'No fixtures yet'}
            >
                {entry.fixtures.length || '–'}
            </span>
        </Link>
    );
}

/** Every view by kind and the UI kit, searchable; `/` focuses the search. */
export default function WorkbenchSidebar({ search }: { search: WorkbenchSearch }) {
    const definitions = useViewDefinitions();
    const [query, setQuery] = useState('');
    const [overridden, setOverridden] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const groups = workbenchGroups(definitions, { query, overridden });

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            const typing = event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement;

            if (event.key === '/' && !typing) {
                event.preventDefault();
                input.current?.focus();
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, []);

    return (
        <nav aria-label='Views' className='flex h-full flex-col'>
            <div className='space-y-2 border-b border-border p-3'>
                <label className='flex items-center gap-2 rounded-sm border border-border bg-background px-2'>
                    <Search className='h-3.5 w-3.5 shrink-0 text-muted-foreground' aria-hidden />
                    <input
                        ref={input}
                        type='search'
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder='Search views  /'
                        aria-label='Search views'
                        className='w-full min-w-0 border-0 bg-transparent px-0 py-1.5 text-sm text-foreground placeholder:text-muted-foreground focus:ring-0 focus:outline-hidden'
                    />
                </label>
                <label className='flex items-center gap-2 text-xs text-muted-foreground'>
                    <input
                        type='checkbox'
                        checked={overridden}
                        onChange={(event) => setOverridden(event.target.checked)}
                        className='h-3.5 w-3.5 rounded-xs border-border bg-input text-primary'
                    />
                    Only views the theme or extensions override
                </label>
            </div>
            <div className='min-h-0 flex-1 overflow-y-auto p-2'>
                {groups.length === 0 && <p className='p-2 text-sm text-muted-foreground'>Nothing matches.</p>}
                {groups.map((group) => (
                    <details key={group.id} open className='mb-2'>
                        <summary className='cursor-pointer select-none px-2 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground'>
                            {group.label} <span className='font-normal'>{group.entries.length}</span>
                        </summary>
                        <ul className='mt-0.5 space-y-px'>
                            {group.entries.map((entry) => (
                                <li key={entry.name}>
                                    <EntryLink
                                        group={group.id}
                                        entry={entry}
                                        search={search}
                                        active={
                                            group.id === 'ui' ? search.ui === entry.name : search.view === entry.name
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    </details>
                ))}
            </div>
        </nav>
    );
}
