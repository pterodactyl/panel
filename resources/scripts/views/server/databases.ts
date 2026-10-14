import { useMemo } from 'react';
import { relationshipAttributes } from '@/api/relationships';
import {
    createServerDatabaseInput,
    deleteServerDatabaseInput,
    rotateServerDatabasePasswordInput,
    useCreateServerDatabase,
    useDeleteServerDatabase,
    useRotateDatabasePassword,
    useServerDatabases,
    type ServerDatabase,
} from '@/api/server/databases/queries';
import { useCurrentServer } from '@/api/server/queries';
import { jdbcConnectionString, serverDatabaseShortName } from '@/lib/databases';
import { usePermissions } from '@/plugins/usePermissions';
import { implementViews } from '../registry';
import { pageStatus, pageStatusFixtures, type PageStatusModel } from '../pageStatus';

export interface ServerDatabaseItem {
    readonly id: string;
    readonly name: string;
    /** The name without the server's prefix; typing either confirms a deletion. */
    readonly shortName: string;
    /** "host:port". */
    readonly endpoint: string;
    readonly connectionsFrom: string;
    readonly username: string;
    /** Null when the user may not see it. */
    readonly password: string | null;
    /** The JDBC URL, with the password when it is known. */
    readonly jdbc: string;
}
export interface ServerDatabasesModel {
    readonly databases: readonly ServerDatabaseItem[];
    /** The list has not loaded yet. */
    readonly loading: boolean;
    /** How many databases the server may have; none at 0. */
    readonly limit: number;
    readonly permissions: {
        readonly create: boolean;
        /** Rotating passwords. */
        readonly update: boolean;
        readonly delete: boolean;
        readonly viewPassword: boolean;
    };
    /** The user may create a database and the limit leaves room for one. */
    readonly canCreate: boolean;
    /** Databases whose password is being rotated. */
    readonly rotating: readonly string[];
    /** The create form's rules: an error message, or undefined when the value is valid. */
    readonly validate: {
        readonly name: (value: string) => string | undefined;
        readonly connectionsFrom: (value: string) => string | undefined;
    };
    /** Core has already shown a toast for a rejection. */
    readonly actions: {
        /** An empty `connectionsFrom` allows connections from anywhere. */
        readonly create: (database: { readonly name: string; readonly connectionsFrom: string }) => Promise<void>;
        readonly rotatePassword: (id: string) => Promise<void>;
        /** Delete without a confirmation; ask the user first. */
        readonly remove: (id: string) => Promise<void>;
    };
}

declare module '../registry' {
    interface ViewModels {
        'page.server.databases': PageStatusModel;
        'server.databases': ServerDatabasesModel;
    }
}

const noDatabases: ServerDatabase[] = [];

function validateName(value: string): string | undefined {
    if (value.length < 1) {
        return 'A database name must be provided.';
    }

    if (value.length < 3) {
        return 'Database name must be at least 3 characters.';
    }

    if (value.length > 48) {
        return 'Database name must not exceed 48 characters.';
    }

    if (!/^[\w\-.]{3,48}$/.test(value)) {
        return 'Database name should only contain alphanumeric characters, underscores, dashes, and/or periods.';
    }

    return undefined;
}

const validateConnectionsFrom = (value: string): string | undefined =>
    /^[\w\-/.%:]*$/.test(value) ? undefined : 'A valid host address must be provided.';

function databaseItem({ attributes }: ServerDatabase): ServerDatabaseItem {
    const endpoint = `${attributes.host.address}:${attributes.host.port}`;
    const password = relationshipAttributes(attributes.relationships?.password)?.password;

    return {
        id: attributes.id,
        name: attributes.name,
        shortName: serverDatabaseShortName(attributes.name),
        endpoint,
        connectionsFrom: attributes.connections_from,
        username: attributes.username,
        password: password ?? null,
        jdbc: jdbcConnectionString({
            connectionString: endpoint,
            name: attributes.name,
            password,
            username: attributes.username,
        }),
    };
}

function useDatabasesQuery() {
    const server = useCurrentServer();

    return { server, query: useServerDatabases(server?.attributes.uuid ?? '') };
}

const useDatabasesPageModel = () => pageStatus(useDatabasesQuery().query);

function useServerDatabasesModel(): ServerDatabasesModel {
    const { server, query } = useDatabasesQuery();
    const uuid = server?.attributes.uuid ?? '';
    const limit = server?.attributes.feature_limits.databases ?? 0;
    const [create, update, remove, viewPassword] = usePermissions([
        'database.create',
        'database.update',
        'database.delete',
        'database.view_password',
    ]);
    const { mutateAsync: createDatabase } = useCreateServerDatabase();
    const { mutateAsync: rotatePassword, isPending: rotating, variables: rotated } = useRotateDatabasePassword();
    const { mutateAsync: deleteDatabase } = useDeleteServerDatabase();
    const listed = query.data?.data ?? noDatabases;
    const rotatingId = rotating ? (rotated?.path.database_id ?? null) : null;

    return useMemo(() => {
        const databases = listed.map(databaseItem);

        return {
            databases,
            loading: !databases.length && query.isLoading,
            limit,
            permissions: { create, update, delete: remove, viewPassword },
            canCreate: create && limit > 0 && databases.length < limit,
            rotating: rotatingId ? [rotatingId] : [],
            validate: { name: validateName, connectionsFrom: validateConnectionsFrom },
            actions: {
                create: async ({ name, connectionsFrom }) => {
                    await createDatabase(
                        createServerDatabaseInput(uuid, { databaseName: name, connectionsFrom: connectionsFrom || '%' })
                    );
                },
                rotatePassword: async (id) => {
                    await rotatePassword(rotateServerDatabasePasswordInput(uuid, id));
                },
                remove: async (id) => {
                    const database = listed.find(({ attributes }) => attributes.id === id);

                    if (database) {
                        await deleteDatabase(deleteServerDatabaseInput(uuid, database));
                    }
                },
            },
        };
    }, [
        listed,
        query.isLoading,
        limit,
        create,
        update,
        remove,
        viewPassword,
        rotatingId,
        createDatabase,
        rotatePassword,
        deleteDatabase,
        uuid,
    ]);
}

const resolved = async () => {};

const database = (name: string, options: Partial<ServerDatabaseItem> = {}): ServerDatabaseItem => ({
    id: name,
    name: `s1_${name}`,
    shortName: name,
    endpoint: '203.0.113.20:3306',
    connectionsFrom: '%',
    username: `u1_${name}`,
    password: 'q8V2mR7xL4',
    jdbc: `jdbc:mysql://203.0.113.20:3306/s1_${name}?user=u1_${name}&password=q8V2mR7xL4`,
    ...options,
});

const databasesFixture = (options: Partial<ServerDatabasesModel> = {}): ServerDatabasesModel => ({
    databases: [database('survival'), database('stats', { connectionsFrom: '10.0.0.%' })],
    loading: false,
    limit: 3,
    permissions: { create: true, update: true, delete: true, viewPassword: true },
    canCreate: true,
    rotating: [],
    validate: { name: validateName, connectionsFrom: validateConnectionsFrom },
    actions: { create: resolved, rotatePassword: resolved, remove: resolved },
    ...options,
});

implementViews({
    'page.server.databases': {
        use: useDatabasesPageModel,
        fixtures: pageStatusFixtures,
    },
    'server.databases': {
        use: useServerDatabasesModel,
        fixtures: {
            databases: () => databasesFixture(),
            full: () => databasesFixture({ limit: 2, canCreate: false }),
            empty: () => databasesFixture({ databases: [] }),
            noLimit: () => databasesFixture({ databases: [], limit: 0, canCreate: false }),
            readOnly: () =>
                databasesFixture({
                    permissions: { create: false, update: false, delete: false, viewPassword: false },
                    canCreate: false,
                    databases: [database('survival', { password: null })],
                }),
            loading: () => databasesFixture({ databases: [], loading: true }),
        },
    },
});
