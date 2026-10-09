import Slot from '@/extensions/Slot';
import type { SubuserPermissionsSlotData } from '@/extensions/registry';
import { permissionGroupRows, replaceEditablePermissions } from './permissionSelection';
import type { Subuser, SubuserPermission } from '@/api/server/users/queries';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import { useCurrentUser } from '@/api/account/queries';
import { useCurrentServer, useCurrentServerPermissions } from '@/api/server/queries';
import {
    createServerSubuserInput,
    updateServerSubuserInput,
    useCreateSubuser,
    useUpdateSubuser,
} from '@/api/server/users/queries';
import { useSystemPermissions } from '@/api/system/queries';
import Can from '@/components/elements/Can';
import { usePermissions } from '@/plugins/usePermissions';
import PermissionTitleBox from '@/components/server/users/PermissionTitleBox';
import PermissionRow from '@/components/server/users/PermissionRow';
import AllPermissionsButton from '@/components/server/users/AllPermissionsButton';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Dialog, type DialogProps } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

type Props = {
    subuser?: Subuser;
    onClose?: () => void;
    onSaved?: (subuser: Subuser) => void;
};

const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

const useSubuserFormState = ({ subuser, onClose, onSaved }: Props) => {
    const server = useCurrentServer();
    const uuid = server?.attributes.uuid ?? '';
    const createSubuser = useCreateSubuser();
    const updateSubuser = useUpdateSubuser();

    const {
        data: permissionsResponse,
        isError: hasPermissionsError,
        isLoading: isLoadingPermissions,
    } = useSystemPermissions();
    const permissions = permissionsResponse?.attributes.permissions ?? {};
    const isRootAdmin = useCurrentUser().rootAdmin;
    const loggedInPermissions = useCurrentServerPermissions();
    const [canEditUser] = usePermissions(subuser ? ['user.update'] : ['user.create']);

    const systemPermissions = Object.entries(permissions).flatMap(([key, permission]) =>
        Object.keys(permission.keys).map((pkey) => `${key}.${pkey}` as SubuserPermission)
    );
    const loggedInPermissionSet = new Set(loggedInPermissions);
    const editablePermissionSet = new Set(
        isRootAdmin || (loggedInPermissions.length === 1 && loggedInPermissions[0] === '*')
            ? systemPermissions
            : systemPermissions.filter((key) => loggedInPermissionSet.has(key))
    );

    const form = useAppForm({
        defaultValues: {
            email: subuser?.attributes.email || '',
            permissions: subuser?.attributes.permissions ?? ([] as SubuserPermission[]),
        },
        onSubmit: async ({ value }) => {
            try {
                const updated = subuser
                    ? await updateSubuser.mutateAsync(updateServerSubuserInput(uuid, subuser, value))
                    : await createSubuser.mutateAsync(createServerSubuserInput(uuid, value));

                onSaved ? onSaved(updated) : onClose?.();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting =
        useStore(form.store, (state) => state.isSubmitting) || createSubuser.isPending || updateSubuser.isPending;

    return {
        canEditUser,
        editablePermissionSet,
        form,
        hasPermissionsError,
        isLoadingPermissions,
        isRootAdmin,
        isSubmitting,
        loggedInPermissions,
        permissions,
        subuser,
    };
};

const validateEmail = (value: string): string | undefined => {
    if (value.length > 191) {
        return 'Email addresses must not exceed 191 characters.';
    }

    return isEmail(value) ? undefined : 'A valid email address must be provided.';
};

type SubuserFormState = ReturnType<typeof useSubuserFormState>;

const SubuserFormTitle = ({ subuser, canEditUser }: { subuser?: Subuser; canEditUser: boolean }) => {
    if (!subuser) {
        return 'Create new subuser';
    }

    return `${canEditUser ? 'Modify' : 'View'} permissions for ${subuser.attributes.email}`;
};

type PermissionGroupsProps = Pick<SubuserFormState, 'form' | 'permissions' | 'canEditUser' | 'editablePermissionSet'>;

const PermissionGroups = ({ form, permissions, canEditUser, editablePermissionSet }: PermissionGroupsProps) =>
    Object.entries(permissions)
        .filter(([key]) => key !== 'websocket')
        .map(([key, permissionGroup], index) => {
            const rows = permissionGroupRows(key, permissionGroup.keys);
            const permissionKeys = rows.map(({ permission }) => permission as SubuserPermission);

            return (
                <PermissionTitleBox
                    key={`permission_${key}`}
                    form={form}
                    title={key}
                    isEditable={canEditUser}
                    editablePermissions={permissionKeys.filter((permission) => editablePermissionSet.has(permission))}
                    className={index > 0 ? 'mt-4' : undefined}
                >
                    <p className='text-sm text-muted-foreground mb-4'>{permissionGroup.description}</p>
                    {rows.map(({ permission, key: label, description }) => (
                        <PermissionRow
                            key={`permission_${permission}`}
                            form={form}
                            permission={permission}
                            label={label}
                            description={description}
                            disabled={!canEditUser || !editablePermissionSet.has(permission as SubuserPermission)}
                        />
                    ))}
                </PermissionTitleBox>
            );
        });

const SubuserFormContent = ({ state }: { state: SubuserFormState }) => {
    const {
        canEditUser,
        editablePermissionSet,
        form,
        hasPermissionsError,
        isLoadingPermissions,
        isRootAdmin,
        isSubmitting,
        loggedInPermissions,
        permissions,
        subuser,
    } = state;
    const selectedPermissions = useStore(form.store, (current) => current.values.permissions);
    // Use the same permission scope as the existing permission controls.
    const editablePermissions = [...editablePermissionSet];

    const permissionSlot: SubuserPermissionsSlotData = {
        mode: subuser ? 'edit' : 'create',
        selectedPermissions,
        editablePermissions: [...editablePermissionSet],
        disabled: !canEditUser || isSubmitting,
        setPermissions: (requested) => {
            if (!canEditUser || isSubmitting || form.state.isSubmitting) {
                return;
            }

            form.setFieldValue(
                'permissions',
                replaceEditablePermissions(form.state.values.permissions, requested, editablePermissionSet)
            );
        },
    };

    if (isLoadingPermissions) {
        return <SpinnerOverlay visible />;
    }

    if (hasPermissionsError) {
        return null;
    }

    return (
        <Form form={form}>
            <SpinnerOverlay visible={isSubmitting} />
            <div className='flex justify-between'>
                <h2 className='text-2xl'>
                    <SubuserFormTitle subuser={subuser} canEditUser={canEditUser} />
                </h2>
                <div>
                    <form.AppForm>
                        <form.SubmitButton className='w-full sm:w-auto'>
                            {subuser ? 'Save' : 'Invite User'}
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </div>
            {!isRootAdmin && loggedInPermissions[0] !== '*' && (
                <div className='mt-4 pl-4 py-2 border-l-4 border-accent'>
                    <p className='text-sm text-muted-foreground'>
                        Only permissions which your account is currently assigned may be selected when creating or
                        modifying other users.
                    </p>
                </div>
            )}
            {!subuser && (
                <div className='mt-6'>
                    <form.AppField
                        name='email'
                        validators={{
                            onChange: ({ value }) => validateEmail(value),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                label='User Email'
                                description='Enter the email address of the user you wish to invite as a subuser for this server.'
                            />
                        )}
                    </form.AppField>
                </div>
            )}
            <Slot name='server.users.permissions.before' data={permissionSlot} />
            <TitledGreyBox
                className='mt-6 border border-border ring-1 ring-border/60 shadow-lg'
                title={
                    <div className='flex items-center justify-between'>
                        <span className='text-sm uppercase'>All Permissions</span>
                        <AllPermissionsButton
                            form={form}
                            editablePermissions={editablePermissions}
                            disabled={!canEditUser || isSubmitting}
                        />
                    </div>
                }
            >
                <p className='m-0 text-sm text-muted-foreground'>
                    Only select this if <strong>you entirely trust the user you are inviting</strong> with all available
                    permissions!
                </p>
            </TitledGreyBox>
            <div className='my-6'>
                <PermissionGroups
                    form={form}
                    permissions={permissions}
                    canEditUser={canEditUser}
                    editablePermissionSet={editablePermissionSet}
                />
            </div>
            <Can action={subuser ? 'user.update' : 'user.create'}>
                <div className='pb-6 flex justify-end'>
                    <form.AppForm>
                        <form.SubmitButton className='w-full sm:w-auto'>
                            {subuser ? 'Save' : 'Invite User'}
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Can>
        </Form>
    );
};

export const SubuserForm = (props: Props) => {
    const state = useSubuserFormState(props);

    return <SubuserFormContent state={state} />;
};

export default function EditSubuserModal({ open, onClose, ...props }: Props & DialogProps) {
    const state = useSubuserFormState({ ...props, onClose });

    return (
        <Dialog
            open={open}
            onClose={onClose}
            preventExternalClose={state.isSubmitting}
            hideCloseIcon={state.isSubmitting}
        >
            <SubuserFormContent state={state} />
        </Dialog>
    );
}
