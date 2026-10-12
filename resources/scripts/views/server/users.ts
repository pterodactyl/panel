import { useCallback, useMemo } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { toast } from 'sonner';
import { useCurrentUser } from '@/api/account/queries';
import { httpErrorToHuman } from '@/api/http';
import { useCurrentServerPermissions } from '@/api/server/queries';
import {
    createServerSubuserInput,
    deleteServerSubuserInput,
    updateServerSubuserInput,
    useCreateSubuser,
    useDeleteSubuser,
    useServerSubusers,
    useUpdateSubuser,
    type Subuser,
} from '@/api/server/users/queries';
import { useSystemPermissions } from '@/api/system/queries';
import type { SubuserPermissionsSlotData } from '@/extensions/registry';
import { usePermissions } from '@/plugins/usePermissions';
import { implementViews } from '../registry';
import { attempt } from './attempt';
import { useLoadedServer } from './loadedServer';
import { permissionGroupRows, replaceEditablePermissions } from './permissionSelection';

export interface SubuserSummary {
    readonly uuid: string;
    readonly email: string;
    /** The user's avatar. */
    readonly image: string;
    readonly twoFactor: boolean;
    readonly permissions: readonly string[];
    /** Granted permissions besides connecting to the console. */
    readonly permissionCount: number;
    /** The viewer themself, whom the list offers no actions for. */
    readonly self: boolean;
}

export interface SubusersPageModel {
    /** The subusers or the permission catalogue are not loaded yet. */
    readonly loading: boolean;
    /** Either failed to load, so the page shows this message instead. */
    readonly error: string | null;
    readonly retry: () => void;
}
export interface SubuserListModel {
    /** The server's identifier, which the link to invite a user includes. */
    readonly serverId: string;
    readonly subusers: readonly SubuserSummary[];
    readonly canCreate: boolean;
    readonly canUpdate: boolean;
    readonly canDelete: boolean;
    readonly deleting: boolean;
    /** Revokes the subuser's access; resolves whether it was removed, a failure having been reported. */
    readonly remove: (uuid: string) => Promise<boolean>;
}

export interface SubuserCreatePageModel {
    readonly serverId: string;
    /** The permission catalogue is not loaded yet. */
    readonly loading: boolean;
}
export interface SubuserPermission {
    /** The full permission, such as `control.start`. */
    readonly key: string;
    /** Its name within the group, such as `start`. */
    readonly label: string;
    readonly description: string;
    /** The viewer holds the permission, so may grant it. */
    readonly editable: boolean;
}
export interface SubuserPermissionGroup {
    readonly name: string;
    readonly description: string;
    readonly permissions: readonly SubuserPermission[];
}
export interface SubuserFormValues {
    readonly email: string;
    readonly permissions: readonly string[];
}
export interface SubuserFormModel {
    readonly mode: 'create' | 'edit';
    /** The subuser being edited; null while inviting one. */
    readonly subuser: SubuserSummary | null;
    /** The permission catalogue is not loaded yet. */
    readonly loading: boolean;
    /** The permission catalogue failed to load. */
    readonly failed: boolean;
    /** The viewer may change the subuser's permissions. */
    readonly canEdit: boolean;
    /** The viewer may only grant the permissions they hold themself. */
    readonly restricted: boolean;
    /** The permission groups, connecting to the console aside. */
    readonly groups: readonly SubuserPermissionGroup[];
    /** Every permission the viewer may grant. */
    readonly editable: readonly string[];
    /** The values the form starts from. */
    readonly values: SubuserFormValues;
    readonly saving: boolean;
    readonly validateEmail: (email: string) => string | undefined;
    /**
     * Saves the subuser and resolves whether it was saved, a failure having been reported. An invite
     * returns to the user list; an edit calls the view's `onClose`.
     */
    readonly save: (values: SubuserFormValues) => Promise<boolean>;
    /** What `server.users.permissions.before` receives: the form's selection, and how to change it. */
    readonly permissionSlot: (
        selected: readonly string[],
        submitting: boolean,
        update: (permissions: string[]) => void
    ) => SubuserPermissionsSlotData;
}
export interface SubuserFormInput {
    /** The subuser to edit; the form invites a new one without it. */
    readonly subuser?: SubuserSummary;
    /** Shows the form in a dialog, closed with this once the subuser is saved. */
    readonly onClose?: () => void;
}

declare module '../registry' {
    interface ViewModels {
        'page.server.users': SubusersPageModel;
        'page.server.users.create': SubuserCreatePageModel;
        'server.subusers': SubuserListModel;
        'server.subuserForm': SubuserFormModel;
    }
    interface ViewInputs {
        'server.subuserForm': SubuserFormInput;
    }
}

const subuserSummary = ({ attributes }: Subuser, viewer: string): SubuserSummary => ({
    uuid: attributes.uuid,
    email: attributes.email,
    image: attributes.image,
    twoFactor: attributes['2fa_enabled'],
    permissions: attributes.permissions,
    permissionCount: attributes.permissions.filter((permission) => permission !== 'websocket.connect').length,
    self: attributes.uuid === viewer,
});

const validateEmail = (value: string): string | undefined => {
    if (value.length > 191) {
        return 'Email addresses must not exceed 191 characters.';
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? undefined : 'A valid email address must be provided.';
};

function useSubusersPageModel(): SubusersPageModel {
    const { uuid } = useLoadedServer().attributes;
    const subusers = useServerSubusers(uuid);
    const groups = useSystemPermissions();
    const permissions = groups.data?.attributes.permissions;
    const subuserError = subusers.error && !subusers.data ? subusers.error : null;
    const groupsError = groups.error && !permissions ? groups.error : null;
    const failure = subuserError ?? groupsError;
    const loading =
        !subusers.data?.data.length &&
        (subusers.isLoading || groups.isLoading || !permissions || !Object.keys(permissions).length);
    const { refetch: refetchSubusers } = subusers;
    const { refetch: refetchGroups } = groups;

    return useMemo(
        () => ({
            loading,
            error: failure ? httpErrorToHuman(failure) : null,
            retry: () => void (subuserError ? refetchSubusers() : refetchGroups()),
        }),
        [loading, failure, subuserError, refetchSubusers, refetchGroups]
    );
}

function useSubuserListModel(): SubuserListModel {
    const { uuid, identifier } = useLoadedServer().attributes;
    const viewer = useCurrentUser().uuid;
    const { data } = useServerSubusers(uuid);
    const [canCreate, canUpdate, canDelete] = usePermissions(['user.create', 'user.update', 'user.delete']);
    const { mutateAsync, isPending: deleting } = useDeleteSubuser();
    const remove = useCallback(
        async (subuserUuid: string) => {
            const subuser = data?.data.find((item) => item.attributes.uuid === subuserUuid);

            return (
                !!subuser &&
                attempt(async () => {
                    await mutateAsync(deleteServerSubuserInput(uuid, subuserUuid));
                    toast.success('Subuser removed', {
                        description: `${subuser.attributes.email} no longer has access to this server.`,
                    });
                })
            );
        },
        [data, mutateAsync, uuid]
    );

    return useMemo(
        () => ({
            serverId: identifier,
            subusers: (data?.data ?? []).map((subuser) => subuserSummary(subuser, viewer)),
            canCreate,
            canUpdate,
            canDelete,
            deleting,
            remove,
        }),
        [identifier, data, viewer, canCreate, canUpdate, canDelete, deleting, remove]
    );
}

function useSubuserCreatePageModel(): SubuserCreatePageModel {
    const { identifier } = useLoadedServer().attributes;
    const permissions = useSystemPermissions().data?.attributes.permissions;
    const loading = !permissions || !Object.keys(permissions).length;

    return useMemo(() => ({ serverId: identifier, loading }), [identifier, loading]);
}

function useSubuserFormModel({ subuser, onClose }: SubuserFormInput): SubuserFormModel {
    const navigate = useNavigate();
    const { uuid, identifier } = useLoadedServer().attributes;
    const { rootAdmin } = useCurrentUser();
    const granted = useCurrentServerPermissions();
    const { data: catalogue, isLoading: loading, isError: failed } = useSystemPermissions();
    const [canEdit] = usePermissions(subuser ? 'user.update' : 'user.create');
    const { mutateAsync: invite, isPending: inviting } = useCreateSubuser();
    const { mutateAsync: update, isPending: updating } = useUpdateSubuser();
    const permissions = catalogue?.attributes.permissions;

    const { groups, editable } = useMemo(() => {
        const entries = Object.entries(permissions ?? {});
        const all = entries.flatMap(([group, { keys }]) =>
            permissionGroupRows(group, keys).map((row) => row.permission)
        );
        const held = new Set(granted);
        const grantable = new Set(
            rootAdmin || (granted.length === 1 && granted[0] === '*') ? all : all.filter((key) => held.has(key))
        );

        return {
            editable: grantable,
            groups: entries
                .filter(([group]) => group !== 'websocket')
                .map(([group, { description, keys }]) => ({
                    name: group,
                    description,
                    permissions: permissionGroupRows(group, keys).map((row) => ({
                        key: row.permission,
                        label: row.key,
                        description: row.description,
                        editable: grantable.has(row.permission),
                    })),
                })),
        };
    }, [permissions, granted, rootAdmin]);

    const save = useCallback(
        (values: SubuserFormValues) => {
            const body = { email: values.email, permissions: [...values.permissions] };

            return attempt(async () => {
                if (subuser) {
                    await update(updateServerSubuserInput(uuid, subuser.uuid, body));
                    onClose?.();

                    return;
                }

                const invited = await invite(createServerSubuserInput(uuid, body));

                toast.success('Subuser invited', { description: `${invited.attributes.email} has been invited.` });
                void navigate({ to: '/server/$id/users', params: { id: identifier } });
            });
        },
        [subuser, onClose, update, invite, navigate, uuid, identifier]
    );
    const saving = inviting || updating;
    const mode = subuser ? 'edit' : 'create';

    const permissionSlot = useCallback(
        (
            selected: readonly string[],
            submitting: boolean,
            apply: (permissions: string[]) => void
        ): SubuserPermissionsSlotData => {
            const disabled = !canEdit || submitting || saving;

            return {
                mode,
                selectedPermissions: selected,
                editablePermissions: [...editable],
                disabled,
                setPermissions: (requested: readonly string[]) => {
                    if (!disabled) {
                        apply(replaceEditablePermissions(selected, requested, editable));
                    }
                },
            };
        },
        [canEdit, saving, mode, editable]
    );

    const restricted = !rootAdmin && granted[0] !== '*';

    return useMemo(
        () => ({
            mode,
            subuser: subuser ?? null,
            loading,
            failed,
            canEdit,
            restricted,
            groups,
            editable: [...editable],
            values: { email: subuser?.email ?? '', permissions: subuser?.permissions ?? [] },
            saving,
            validateEmail,
            save,
            permissionSlot,
        }),
        [mode, subuser, loading, failed, canEdit, restricted, groups, editable, saving, save, permissionSlot]
    );
}

const saved = async () => true;
const noop = () => {};

const viewer: SubuserSummary = {
    uuid: '11111111-1111-4111-8111-111111111111',
    email: 'alex@example.test',
    image: 'https://www.gravatar.com/avatar/00000000000000000000000000000000',
    twoFactor: true,
    permissions: ['websocket.connect', 'control.console', 'control.start', 'control.stop', 'control.restart'],
    permissionCount: 4,
    self: true,
};
const subuserFixtures: readonly SubuserSummary[] = [
    viewer,
    {
        uuid: '22222222-2222-4222-8222-222222222222',
        email: 'sam@example.test',
        image: 'https://www.gravatar.com/avatar/11111111111111111111111111111111',
        twoFactor: false,
        permissions: ['websocket.connect', 'control.console', 'backup.read', 'backup.create'],
        permissionCount: 3,
        self: false,
    },
    {
        uuid: '33333333-3333-4333-8333-333333333333',
        email: 'jordan@example.test',
        image: 'https://www.gravatar.com/avatar/22222222222222222222222222222222',
        twoFactor: true,
        permissions: ['websocket.connect', 'user.read', 'user.create', 'user.update', 'user.delete'],
        permissionCount: 4,
        self: false,
    },
];

const listFixture = (options: Partial<SubuserListModel> = {}): SubuserListModel => ({
    serverId: 'a1b2c3d4',
    subusers: subuserFixtures,
    canCreate: true,
    canUpdate: true,
    canDelete: true,
    deleting: false,
    remove: saved,
    ...options,
});

const permissionCatalogue: readonly (readonly [string, string, Readonly<Record<string, string>>])[] = [
    [
        'control',
        "Permissions that control a user's ability to control the power state of a server, or send commands.",
        {
            console: 'Allows a user to send commands to the server instance via the console.',
            start: 'Allows a user to start the server if it is stopped.',
            stop: 'Allows a user to stop a server if it is running.',
            restart: 'Allows a user to perform a server restart.',
        },
    ],
    [
        'user',
        'Permissions that allow a user to manage other subusers on a server.',
        {
            create: 'Allows a user to create new subusers for the server.',
            read: 'Allows the user to view subusers and their permissions for the server.',
            update: 'Allows a user to modify other subusers.',
            delete: 'Allows a user to delete a subuser from the server.',
        },
    ],
    [
        'backup',
        "Permissions that control a user's ability to generate and manage server backups.",
        {
            create: 'Allows a user to create new backups for this server.',
            read: 'Allows a user to view all backups that exist for this server.',
            delete: 'Allows a user to remove backups from the system.',
        },
    ],
];

const groupsFixture = (held?: ReadonlySet<string>): SubuserPermissionGroup[] =>
    permissionCatalogue.map(([name, description, keys]) => ({
        name,
        description,
        permissions: permissionGroupRows(name, keys).map((row) => ({
            key: row.permission,
            label: row.key,
            description: row.description,
            editable: !held || held.has(row.permission),
        })),
    }));

const formFixture = (options: Partial<SubuserFormModel> = {}): SubuserFormModel => {
    const groups = options.groups ?? groupsFixture();
    const editable = groups.flatMap((group) => group.permissions.filter((row) => row.editable).map((row) => row.key));

    return {
        mode: 'create',
        subuser: null,
        loading: false,
        failed: false,
        canEdit: true,
        restricted: false,
        groups,
        editable: ['websocket.connect', ...editable],
        values: { email: '', permissions: [] },
        saving: false,
        validateEmail,
        save: saved,
        permissionSlot: (selected, submitting) => ({
            mode: options.mode ?? 'create',
            selectedPermissions: selected,
            editablePermissions: ['websocket.connect', ...editable],
            disabled: submitting,
            setPermissions: noop,
        }),
        ...options,
    };
};

const editing = subuserFixtures[1];

implementViews({
    'page.server.users': {
        use: useSubusersPageModel,
        fixtures: {
            default: () => ({ loading: false, error: null, retry: noop }),
            loading: () => ({ loading: true, error: null, retry: noop }),
            error: () => ({ loading: false, error: 'Request failed with status code 500.', retry: noop }),
        },
    },
    'server.subusers': {
        use: useSubuserListModel,
        fixtures: {
            subusers: () => listFixture(),
            empty: () => listFixture({ subusers: [] }),
            readOnly: () => listFixture({ canCreate: false, canUpdate: false, canDelete: false }),
        },
    },
    'page.server.users.create': {
        use: useSubuserCreatePageModel,
        fixtures: {
            default: () => ({ serverId: 'a1b2c3d4', loading: false }),
            loading: () => ({ serverId: 'a1b2c3d4', loading: true }),
        },
    },
    'server.subuserForm': {
        use: useSubuserFormModel,
        fixtureInput: (model) => (model.subuser ? { subuser: model.subuser } : {}),
        fixtures: {
            create: () => formFixture(),
            edit: () =>
                formFixture({
                    mode: 'edit',
                    subuser: editing,
                    values: { email: editing.email, permissions: editing.permissions },
                }),
            restricted: () =>
                formFixture({
                    restricted: true,
                    groups: groupsFixture(new Set(['control.console', 'control.start', 'backup.read'])),
                }),
            viewOnly: () =>
                formFixture({
                    mode: 'edit',
                    subuser: editing,
                    canEdit: false,
                    values: { email: editing.email, permissions: editing.permissions },
                }),
            loading: () => formFixture({ loading: true }),
        },
    },
});
