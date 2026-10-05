/** @vitest-environment jsdom */

import type { ComponentProps } from 'react';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type * as NodeQueries from '@/api/admin/nodes/queries';
import type { AdminDatabaseHost } from '@/api/admin/database-hosts/queries';
import type { AdminLocation } from '@/api/admin/locations/queries';
import type { AdminMount } from '@/api/admin/mounts/queries';
import type { AdminNode } from '@/api/admin/nodes/queries';
import type { AdminUser } from '@/api/admin/users/queries';
import { databaseHostColumns } from '@/components/admin/databases/DatabaseHostTable';
import { locationColumns } from '@/components/admin/locations/LocationTable';
import { mountColumns } from '@/components/admin/mounts/MountTable';
import { nodeColumns } from '@/components/admin/nodes/NodeTable';
import { userColumns } from '@/components/admin/users/UserTable';
import DataTable from '@/components/elements/table/DataTable';

vi.mock('@tanstack/react-router', () => ({
    Link: ({
        to,
        params,
        search: _search,
        ...props
    }: ComponentProps<'a'> & { to: string; params?: Record<string, number>; search?: object }) => (
        <a
            href={Object.entries(params ?? {}).reduce(
                (path, [key, value]) => path.replace(`$${key}`, String(value)),
                to
            )}
            {...props}
        />
    ),
}));
vi.mock('@/api/admin/nodes/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof NodeQueries>()),
    useAdminNodeSystemInformation: () => ({ data: undefined, error: null }),
}));

afterEach(cleanup);

const createdAt = '2026-09-01T00:00:00Z';

const node = {
    object: 'node',
    attributes: {
        id: 3,
        name: 'Node One',
        fqdn: 'node.example.com',
        location_id: 1,
        servers_count: 0,
        memory: 1024,
        disk: 2048,
        scheme: 'https',
        public: true,
        maintenance_mode: false,
        created_at: createdAt,
    },
} as AdminNode;

const user = {
    object: 'user',
    attributes: {
        id: 5,
        uuid: 'user-uuid',
        username: 'admin',
        email: 'admin@example.com',
        first_name: 'Ada',
        last_name: 'Admin',
        image: 'https://example.com/avatar',
        root_admin: true,
        '2fa': false,
        servers_count: 0,
        created_at: createdAt,
    },
} as AdminUser;

const location = {
    object: 'location',
    attributes: {
        id: 2,
        short: 'us-east',
        long: '',
        nodes_count: 0,
        servers_count: 0,
        created_at: createdAt,
        updated_at: createdAt,
    },
} as AdminLocation;

const mount = {
    object: 'mount',
    attributes: {
        id: 9,
        uuid: 'mount-uuid',
        name: 'Shared Maps',
        description: '',
        source: '/srv/maps',
        target: '/home/container/maps',
        read_only: false,
        user_mountable: false,
        eggs_count: 0,
        nodes_count: 0,
        servers_count: 0,
    },
} as AdminMount;

const host = {
    object: 'database_host',
    attributes: {
        id: 4,
        name: 'Primary MySQL',
        host: 'mysql.internal',
        port: 3306,
        username: 'pterodactyl',
        databases_count: 0,
        node_id: null,
        created_at: createdAt,
    },
} as AdminDatabaseHost;

function SingleRowTable<TData>({ row, columns }: { row: TData; columns: ColumnDef<TData>[] }) {
    const table = useReactTable({ data: [row], columns, getCoreRowModel: getCoreRowModel() });

    return <DataTable table={table} emptyState={'No rows'} />;
}

describe('admin resource tables', () => {
    it('only enables database-host sorting for API-backed columns', () => {
        expect(databaseHostColumns.filter((column) => column.enableSorting).map((column) => column.id)).toEqual([
            'name',
            'created_at',
        ]);
    });

    it('only enables mount sorting for API-backed columns', () => {
        expect(mountColumns.filter((column) => column.enableSorting).map((column) => column.id)).toEqual(['name']);
    });

    it.each([
        ['node', () => <SingleRowTable row={node} columns={nodeColumns} />, 'Edit Node One', '/panel/nodes/3/settings'],
        ['user', () => <SingleRowTable row={user} columns={userColumns} />, 'Edit admin@example.com', '/panel/users/5'],
        [
            'location',
            () => <SingleRowTable row={location} columns={locationColumns} />,
            'Edit us-east',
            '/panel/locations/2',
        ],
        ['mount', () => <SingleRowTable row={mount} columns={mountColumns} />, 'Edit Shared Maps', '/panel/mounts/9'],
        [
            'database host',
            () => <SingleRowTable row={host} columns={databaseHostColumns} />,
            'Edit Primary MySQL',
            '/panel/databases/4',
        ],
    ])('links the %s edit action to its detail page', (_resource, renderTable, label, href) => {
        render(renderTable());

        expect(screen.getByRole('link', { name: label })).toHaveAttribute('href', href);
        expect(screen.queryByRole('button', { name: label })).not.toBeInTheDocument();
    });
});
