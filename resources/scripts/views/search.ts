import { useMemo, useState } from 'react';
import { useCurrentUser } from '@/api/account/queries';
import { useAccountServerSearch } from '@/api/account/servers/queries';
import { httpErrorToHuman } from '@/api/http';
import { relationshipData } from '@/api/relationships';
import type { Server } from '@/api/server/types';
import { ip } from '@/lib/formatters';
import { useDebouncedValue } from '@/plugins/useDebouncedValue';
import { describeShortcut, useShortcut, type Shortcut } from '@/plugins/useShortcut';
import type { CorePanelDestination } from '@/sdk/navigation';
import { createTestServer } from './fixtures';
import { fixtureField, stateField, type FieldModel } from './forms';
import { implementViews } from './registry';

/** A server the search found. */
export interface AppSearchResult {
    readonly identifier: string;
    readonly uuid: string;
    readonly name: string;
    /** Its primary allocations as "host:port". */
    readonly addresses: readonly string[];
    readonly node: string;
    readonly destination: CorePanelDestination;
}
/**
 * The server search: a dialog opened by the trigger the view draws, or by Cmd+/ (Ctrl+/) while the view
 * is on the page. The term is searched once it has three characters, half a second after typing stops.
 */
export interface AppSearchModel {
    readonly open: boolean;
    readonly show: () => void;
    readonly close: () => void;
    /** The shortcut that opens the search, as this device names it, for `aria-keyshortcuts` and a hint. */
    readonly shortcut: Shortcut;
    /** The search term for an input; its error asks for three characters. */
    readonly field: FieldModel;
    /** The first five servers that match. */
    readonly results: readonly AppSearchResult[];
    readonly loading: boolean;
    /** The term has been searched, so no results means nothing matched. */
    readonly searched: boolean;
    readonly error: string | null;
}

declare module './registry' {
    interface ViewModels {
        'app.search': AppSearchModel;
    }
}

const SHORT = 'Please enter at least three characters to begin searching.';

const searchResult = ({ attributes }: Server): AppSearchResult => ({
    identifier: attributes.identifier,
    uuid: attributes.uuid,
    name: attributes.name,
    addresses: relationshipData(attributes.relationships?.allocations)
        .filter((allocation) => allocation.attributes.is_default)
        .map(({ attributes: allocation }) => `${allocation.ip_alias || ip(allocation.ip)}:${allocation.port}`),
    node: attributes.node,
    destination: { to: '/server/$id', params: { id: attributes.identifier } },
});

const shortcut = describeShortcut('mod+/');

function useAppSearchModel(): AppSearchModel {
    const { rootAdmin } = useCurrentUser();
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState('');
    const query = useDebouncedValue(term, 500).trim();
    const enabled = open && query.length >= 3;
    const { data, error, isFetching } = useAccountServerSearch(
        { query, type: rootAdmin ? 'admin-all' : undefined },
        enabled
    );

    useShortcut('mod+/', () => setOpen(true), { when: !open });

    return useMemo(() => {
        const field = stateField('term', term, setTerm);

        return {
            open,
            show: () => setOpen(true),
            close: () => {
                setOpen(false);
                setTerm('');
            },
            shortcut,
            field: { ...field, error: term.length > 0 && term.length < 3 ? SHORT : null },
            results: enabled ? (data?.data.slice(0, 5) ?? []).map(searchResult) : [],
            loading: enabled && isFetching,
            searched: enabled && !isFetching && !error && data !== undefined,
            error: enabled && error && !isFetching ? httpErrorToHuman(error) : null,
        };
    }, [open, term, enabled, data, error, isFetching]);
}

const noop = () => {};

const results = [
    createTestServer({ identifier: 'a1b2c3d4', uuid: 'a1b2c3d4-0000-4000-8000-000000000001', name: 'Survival' }),
    createTestServer({ identifier: 'e5f6a7b8', uuid: 'e5f6a7b8-0000-4000-8000-000000000002', name: 'Survival Tests' }),
].map(searchResult);

const searchFixture = (options: Partial<AppSearchModel> = {}): AppSearchModel => ({
    open: true,
    show: noop,
    close: noop,
    shortcut,
    field: fixtureField('term', {}, 'surv'),
    results,
    loading: false,
    searched: true,
    error: null,
    ...options,
});

implementViews({
    'app.search': {
        use: useAppSearchModel,
        fixtures: {
            closed: () => searchFixture({ open: false, field: fixtureField('term'), results: [], searched: false }),
            results: () => searchFixture(),
            loading: () => searchFixture({ results: [], loading: true, searched: false }),
            none: () => searchFixture({ field: fixtureField('term', {}, 'zzz'), results: [] }),
            short: () => searchFixture({ field: fixtureField('term', {}, 'su', SHORT), results: [], searched: false }),
            error: () => searchFixture({ results: [], searched: false, error: 'Request failed with status code 500.' }),
        },
    },
});
