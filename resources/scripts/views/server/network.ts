import { useMemo } from 'react';
import { httpErrorToHuman } from '@/api/http';
import {
    createServerAllocationInput,
    deleteServerAllocationInput,
    setPrimaryAllocationInput,
    updateAllocationNotesInput,
    useCreateServerAllocation,
    useDeleteServerAllocation,
    useServerAllocations,
    useSetPrimaryAllocation,
    useUpdateAllocationNotes,
    type ServerAllocation,
} from '@/api/server/network/queries';
import { useCurrentServer } from '@/api/server/queries';
import { ip } from '@/lib/formatters';
import { usePermissions } from '@/plugins/usePermissions';
import { implementViews } from '../registry';
import { serverPageFixtures, type ServerPageModel } from './page';

export interface ServerAllocationItem {
    readonly id: number;
    readonly ip: string;
    readonly alias: string | null;
    /** The alias, or the IP in brackets when it is IPv6. */
    readonly address: string;
    readonly port: number;
    readonly notes: string | null;
    /** The allocation the server binds to; it cannot be removed. */
    readonly primary: boolean;
}
export interface ServerAllocationsModel {
    readonly allocations: readonly ServerAllocationItem[];
    /** The list has not loaded yet. */
    readonly loading: boolean;
    /** How many allocations the server may have; at 0 they are managed by an administrator. */
    readonly limit: number;
    readonly permissions: { readonly create: boolean; readonly update: boolean; readonly delete: boolean };
    /** The user may add allocations and the server has a limit to add them under. */
    readonly canManage: boolean;
    /** `canManage`, with room left under the limit. */
    readonly canCreate: boolean;
    /** Core has already shown a toast for a rejection. */
    readonly actions: {
        /** Assigns another allocation from the node. */
        readonly create: () => Promise<void>;
        readonly setPrimary: (id: number) => Promise<void>;
        readonly setNotes: (id: number, notes: string) => Promise<void>;
        /** Remove without a confirmation; ask the user first. */
        readonly remove: (id: number) => Promise<void>;
    };
}

declare module '../registry' {
    interface ViewModels {
        'page.server.network': ServerPageModel;
        'server.allocations': ServerAllocationsModel;
    }
}

const noAllocations: ServerAllocation[] = [];

const allocationItem = ({ attributes }: ServerAllocation): ServerAllocationItem => ({
    id: attributes.id,
    ip: attributes.ip,
    alias: attributes.ip_alias,
    address: attributes.ip_alias || ip(attributes.ip),
    port: attributes.port,
    notes: attributes.notes,
    primary: attributes.is_default,
});

function useAllocationsQuery() {
    const server = useCurrentServer();

    return { server, query: useServerAllocations(server?.attributes.uuid ?? '') };
}

function useNetworkPageModel(): ServerPageModel {
    const { data, error, refetch } = useAllocationsQuery().query;

    return useMemo(
        () => ({ error: error && !data ? httpErrorToHuman(error) : null, retry: () => void refetch() }),
        [data, error, refetch]
    );
}

function useServerAllocationsModel(): ServerAllocationsModel {
    const { server, query } = useAllocationsQuery();
    const uuid = server?.attributes.uuid ?? '';
    const limit = server?.attributes.feature_limits.allocations ?? 0;
    const [create, update, remove] = usePermissions(['allocation.create', 'allocation.update', 'allocation.delete']);
    const { mutateAsync: createAllocation } = useCreateServerAllocation();
    const { mutateAsync: setPrimary } = useSetPrimaryAllocation();
    const { mutateAsync: setNotes } = useUpdateAllocationNotes();
    const { mutateAsync: deleteAllocation } = useDeleteServerAllocation();
    const listed = query.data?.data ?? noAllocations;
    const loading = query.data === undefined;

    return useMemo(() => {
        const allocations = listed.map(allocationItem);
        const canManage = create && limit > 0;

        return {
            allocations,
            loading,
            limit,
            permissions: { create, update, delete: remove },
            canManage,
            canCreate: canManage && limit > allocations.length,
            actions: {
                create: async () => {
                    await createAllocation(createServerAllocationInput(uuid));
                },
                setPrimary: async (id) => {
                    await setPrimary(setPrimaryAllocationInput(uuid, id));
                },
                setNotes: async (id, notes) => {
                    await setNotes(updateAllocationNotesInput(uuid, id, notes));
                },
                remove: async (id) => {
                    await deleteAllocation(deleteServerAllocationInput(uuid, id));
                },
            },
        };
    }, [
        listed,
        loading,
        limit,
        create,
        update,
        remove,
        createAllocation,
        setPrimary,
        setNotes,
        deleteAllocation,
        uuid,
    ]);
}

const resolved = async () => {};

const allocation = (id: number, port: number, options: Partial<ServerAllocationItem> = {}): ServerAllocationItem => ({
    id,
    ip: '203.0.113.10',
    alias: null,
    address: '203.0.113.10',
    port,
    notes: null,
    primary: false,
    ...options,
});

const allocationsFixture = (options: Partial<ServerAllocationsModel> = {}): ServerAllocationsModel => ({
    allocations: [
        allocation(1, 25565, { primary: true, notes: 'Game port' }),
        allocation(2, 25575, { notes: 'RCON' }),
        allocation(3, 8123, { alias: 'play.example.com', address: 'play.example.com' }),
    ],
    loading: false,
    limit: 5,
    permissions: { create: true, update: true, delete: true },
    canManage: true,
    canCreate: true,
    actions: { create: resolved, setPrimary: resolved, setNotes: resolved, remove: resolved },
    ...options,
});

implementViews({
    'page.server.network': {
        use: useNetworkPageModel,
        fixtures: serverPageFixtures,
    },
    'server.allocations': {
        use: useServerAllocationsModel,
        fixtures: {
            allocations: () => allocationsFixture(),
            full: () => allocationsFixture({ limit: 3, canCreate: false }),
            unmanaged: () => allocationsFixture({ limit: 0, canManage: false, canCreate: false }),
            readOnly: () =>
                allocationsFixture({
                    permissions: { create: false, update: false, delete: false },
                    canManage: false,
                    canCreate: false,
                }),
            empty: () => allocationsFixture({ allocations: [] }),
            loading: () => allocationsFixture({ allocations: [], loading: true }),
        },
    },
});
