import { useCallback, useMemo } from 'react';
import { useCurrentUser } from '@/api/account/queries';
import { useReinstallServer, useRenameServer } from '@/api/server/queries';
import { canReinstallServer, SKIPPED_INSTALL_SCRIPT_MESSAGE } from '@/lib/serverStatus';
import { usePermissions } from '@/plugins/usePermissions';
import { viewPage } from '../pages';
import { defineView } from '../registry';
import { attempt } from './attempt';
import { useLoadedServer } from './loadedServer';

export type ServerSettingsPageModel = Readonly<Record<never, never>>;

export interface ServerSftpModel {
    /** The viewer may use SFTP; the block renders nothing otherwise. */
    readonly allowed: boolean;
    readonly ip: string;
    readonly port: number;
    /** The SFTP username, which signs in with the viewer's panel password. */
    readonly username: string;
}

export interface ServerDebugModel {
    /** The name of the node running the server. */
    readonly node: string;
    readonly uuid: string;
}

export interface ServerDetailsValues {
    readonly name: string;
    readonly description: string;
}
export interface ServerRenameModel {
    readonly allowed: boolean;
    /** The details as saved, which the form starts from. */
    readonly values: ServerDetailsValues;
    readonly validateName: (name: string) => string | undefined;
    /** Resolves whether the details were saved; a failure has already been reported. */
    readonly save: (values: ServerDetailsValues) => Promise<boolean>;
}

export interface ServerReinstallModel {
    readonly allowed: boolean;
    /** Why the server cannot be reinstalled, or null when it can. */
    readonly unavailable: string | null;
    /** Resolves whether the reinstall started; a failure has already been reported. */
    readonly reinstall: () => Promise<boolean>;
}

declare module '../registry' {
    interface ViewModels {
        'page.server.settings': ServerSettingsPageModel;
        'server.sftp': ServerSftpModel;
        'server.debug': ServerDebugModel;
        'server.rename': ServerRenameModel;
        'server.reinstall': ServerReinstallModel;
    }
}

const settingsPageModel: ServerSettingsPageModel = Object.freeze({});

function useSftpModel(): ServerSftpModel {
    const { username } = useCurrentUser();
    const { identifier, sftp_details: sftp } = useLoadedServer().attributes;
    const [allowed] = usePermissions('file.sftp');

    return useMemo(
        () => ({ allowed, ip: sftp.ip, port: sftp.port, username: `${username}.${identifier}` }),
        [allowed, sftp, username, identifier]
    );
}

function useDebugModel(): ServerDebugModel {
    const { node, uuid } = useLoadedServer().attributes;

    return useMemo(() => ({ node, uuid }), [node, uuid]);
}

const validateName = (name: string): string | undefined => (name.length >= 1 ? undefined : 'Required');

function useRenameModel(): ServerRenameModel {
    const { identifier, uuid, name, description } = useLoadedServer().attributes;
    const [allowed] = usePermissions('settings.rename');
    const { mutateAsync } = useRenameServer(identifier);
    const save = useCallback(
        (values: ServerDetailsValues) =>
            attempt(() =>
                mutateAsync({
                    path: { server_uuid: uuid },
                    body: { name: values.name, description: values.description },
                })
            ),
        [mutateAsync, uuid]
    );

    return useMemo(
        () => ({ allowed, values: { name, description: description ?? '' }, validateName, save }),
        [allowed, name, description, save]
    );
}

function useReinstallModel(): ServerReinstallModel {
    const { uuid, status, skip_scripts: skipScripts } = useLoadedServer().attributes;
    const [allowed] = usePermissions('settings.reinstall');
    const { mutateAsync } = useReinstallServer();
    const reinstall = useCallback(
        () => attempt(() => mutateAsync({ path: { server_uuid: uuid } })),
        [mutateAsync, uuid]
    );
    const unavailable = canReinstallServer(status, skipScripts) ? null : SKIPPED_INSTALL_SCRIPT_MESSAGE;

    return useMemo(() => ({ allowed, unavailable, reinstall }), [allowed, unavailable, reinstall]);
}

const succeeded = async () => true;

const sftpFixture = (options: Partial<ServerSftpModel> = {}): ServerSftpModel => ({
    allowed: true,
    ip: '203.0.113.10',
    port: 2022,
    username: 'alex.a1b2c3d4',
    ...options,
});

const renameFixture = (options: Partial<ServerRenameModel> = {}): ServerRenameModel => ({
    allowed: true,
    values: { name: 'Survival', description: 'The main survival world.' },
    validateName,
    save: succeeded,
    ...options,
});

export const settingsViews = [
    defineView('page.server.settings', {
        ...viewPage('page.server.settings'),
        use: () => settingsPageModel,
        fixtures: { default: () => settingsPageModel },
    }),
    defineView('server.sftp', {
        context: 'server',
        use: useSftpModel,
        fixtures: {
            default: () => sftpFixture(),
            ipv6: () => sftpFixture({ ip: '2001:db8::10' }),
            denied: () => sftpFixture({ allowed: false }),
        },
    }),
    defineView('server.debug', {
        context: 'server',
        use: useDebugModel,
        fixtures: { default: () => ({ node: 'Node 1', uuid: '11111111-1111-4111-8111-111111111111' }) },
    }),
    defineView('server.rename', {
        context: 'server',
        use: useRenameModel,
        fixtures: {
            default: () => renameFixture(),
            empty: () => renameFixture({ values: { name: 'Survival', description: '' } }),
            denied: () => renameFixture({ allowed: false }),
        },
    }),
    defineView('server.reinstall', {
        context: 'server',
        use: useReinstallModel,
        fixtures: {
            default: () => ({ allowed: true, unavailable: null, reinstall: succeeded }),
            skipped: () => ({ allowed: true, unavailable: SKIPPED_INSTALL_SCRIPT_MESSAGE, reinstall: succeeded }),
            denied: () => ({ allowed: false, unavailable: null, reinstall: succeeded }),
        },
    }),
];
