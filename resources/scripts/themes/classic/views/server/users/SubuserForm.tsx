import type { ComponentProps } from 'react';
import {
    Card,
    Checkbox,
    cn,
    Dialog,
    Form,
    Label,
    setPermissionsSelected,
    SpinnerOverlay,
    useAppForm,
    type AppForm,
    type SubuserFormModel,
    type SubuserPermission,
    type SubuserPermissionGroup,
} from '@/sdk';
import { Slot, type ViewProps } from '@/sdk/theme';

type Values = { email: string; permissions: string[] };
type SubuserAppForm = AppForm<Values>;

interface Selection {
    selected: readonly string[];
    select: (permissions: string[]) => void;
    /** The viewer may change the selection right now. */
    enabled: boolean;
}

const PermissionRowContainer = ({ className, ...props }: ComponentProps<'label'> & { htmlFor: string }) => (
    <label
        className={cn(
            'flex items-center border border-transparent rounded-sm md:p-2 transition-colors duration-75 normal-case',
            '[&:not(.disabled)]:cursor-pointer [&:not(.disabled):hover]:border-border [&:not(.disabled):hover]:bg-background',
            'not-first-of-type:mt-4 sm:not-first-of-type:mt-2',
            '[&.disabled]:opacity-50',
            className
        )}
        {...props}
    />
);

function PermissionRow({
    permission,
    disabled,
    selection,
}: {
    permission: SubuserPermission;
    disabled: boolean;
    selection: Selection;
}) {
    const id = `permission_${permission.key}`;

    return (
        <PermissionRowContainer htmlFor={id} className={disabled ? 'disabled' : undefined}>
            <div className='p-2'>
                <Checkbox
                    id={id}
                    checked={selection.selected.includes(permission.key)}
                    disabled={disabled}
                    onChange={(checked) =>
                        selection.select(setPermissionsSelected(selection.selected, [permission.key], checked))
                    }
                    className='w-5 h-5 mr-2'
                />
            </div>
            <div className='flex-1'>
                <Label as='p' className='font-medium'>
                    {permission.label}
                </Label>
                {permission.description.length > 0 && (
                    <p className='text-xs text-muted-foreground mt-1'>{permission.description}</p>
                )}
            </div>
        </PermissionRowContainer>
    );
}

/** Checks every permission given, half-checked while only some are selected. */
function SelectAll({
    permissions,
    selection,
    label,
    disabled = false,
}: {
    permissions: readonly string[];
    selection: Selection;
    label: string;
    disabled?: boolean;
}) {
    const selected = new Set(selection.selected);
    const all = permissions.length > 0 && permissions.every((permission) => selected.has(permission));

    return (
        <Checkbox
            aria-label={label}
            checked={all}
            indeterminate={!all && permissions.some((permission) => selected.has(permission))}
            disabled={disabled}
            onChange={(checked) => selection.select(setPermissionsSelected(selection.selected, permissions, checked))}
            className='w-5 h-5'
        />
    );
}

function PermissionGroup({
    group,
    model,
    selection,
    className,
}: {
    group: SubuserPermissionGroup;
    model: SubuserFormModel;
    selection: Selection;
    className?: string;
}) {
    const editable = group.permissions.filter((permission) => permission.editable).map((permission) => permission.key);

    return (
        <Card
            className={className}
            title={
                <div className='flex items-center'>
                    <p className='text-sm uppercase flex-1'>{group.name}</p>
                    {model.canEdit && editable.length > 0 && (
                        <SelectAll
                            permissions={editable}
                            selection={selection}
                            label={`Select all ${group.name} permissions`}
                        />
                    )}
                </div>
            }
        >
            <p className='text-sm text-muted-foreground mb-4'>{group.description}</p>
            {group.permissions.map((permission) => (
                <PermissionRow
                    key={`permission_${permission.key}`}
                    permission={permission}
                    disabled={!model.canEdit || !permission.editable}
                    selection={selection}
                />
            ))}
        </Card>
    );
}

function SubmitButton({ form, model }: { form: SubuserAppForm; model: SubuserFormModel }) {
    return (
        <form.AppForm>
            <form.SubmitButton className='w-full sm:w-auto'>{model.subuser ? 'Save' : 'Invite User'}</form.SubmitButton>
        </form.AppForm>
    );
}

function FormContent({
    form,
    model,
    submitting,
}: {
    form: SubuserAppForm;
    model: SubuserFormModel;
    submitting: boolean;
}) {
    if (model.loading) {
        return <SpinnerOverlay visible />;
    }

    if (model.failed) {
        return null;
    }

    return (
        <form.Subscribe selector={(state) => state.values.permissions}>
            {(selected) => {
                const selection: Selection = {
                    selected,
                    select: (permissions) => form.setFieldValue('permissions', permissions),
                    enabled: model.canEdit && !submitting && !model.saving,
                };
                const title = model.subuser
                    ? `${model.canEdit ? 'Modify' : 'View'} permissions for ${model.subuser.email}`
                    : 'Create new subuser';

                return (
                    <Form form={form}>
                        <SpinnerOverlay visible={submitting || model.saving} />
                        <div className='flex justify-between'>
                            <h2 className='text-2xl'>{title}</h2>
                            <div>
                                <SubmitButton form={form} model={model} />
                            </div>
                        </div>
                        {model.restricted && (
                            <div className='mt-4 pl-4 py-2 border-l-4 border-accent'>
                                <p className='text-sm text-muted-foreground'>
                                    Only permissions which your account is currently assigned may be selected when
                                    creating or modifying other users.
                                </p>
                            </div>
                        )}
                        {!model.subuser && (
                            <div className='mt-6'>
                                <form.AppField
                                    name='email'
                                    validators={{ onChange: ({ value }) => model.validateEmail(value) }}
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
                        <Slot
                            name='server.users.permissions.before'
                            data={model.permissionSlot(selected, submitting, selection.select)}
                        />
                        <Card
                            className='mt-6'
                            title={
                                <div className='flex items-center justify-between'>
                                    <span className='text-sm uppercase'>All Permissions</span>
                                    {model.editable.length > 0 && (
                                        <div className='flex items-center gap-2 text-sm'>
                                            <SelectAll
                                                permissions={model.editable}
                                                selection={selection}
                                                label='Select all permissions'
                                                disabled={!selection.enabled}
                                            />
                                        </div>
                                    )}
                                </div>
                            }
                        >
                            <p className='m-0 text-sm text-muted-foreground'>
                                Only select this if <strong>you entirely trust the user you are inviting</strong> with
                                all available permissions!
                            </p>
                        </Card>
                        <div className='my-6'>
                            {model.groups.map((group, index) => (
                                <PermissionGroup
                                    key={`permission_${group.name}`}
                                    group={group}
                                    model={model}
                                    selection={selection}
                                    className={index > 0 ? 'mt-4' : undefined}
                                />
                            ))}
                        </div>
                        {model.canEdit && (
                            <div className='pb-6 flex justify-end'>
                                <SubmitButton form={form} model={model} />
                            </div>
                        )}
                    </Form>
                );
            }}
        </form.Subscribe>
    );
}

/** Invites a subuser, or edits one in a dialog when the view is given `onClose`. */
export default function SubuserForm({ model, onClose, className }: ViewProps<'server.subuserForm'>) {
    const defaultValues: Values = { email: model.values.email, permissions: [...model.values.permissions] };
    const form = useAppForm({
        defaultValues,
        onSubmit: async ({ value }) => {
            await model.save(value);
        },
    });

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(submitting) => {
                const content = (
                    <div className={className}>
                        <FormContent form={form} model={model} submitting={submitting} />
                    </div>
                );

                if (!onClose) {
                    return content;
                }

                const busy = submitting || model.saving;

                return (
                    <Dialog open onClose={onClose} preventExternalClose={busy} hideCloseIcon={busy}>
                        {content}
                    </Dialog>
                );
            }}
        </form.Subscribe>
    );
}
