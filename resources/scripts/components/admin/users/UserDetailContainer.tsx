import { useMemo } from 'react';
import { Link, Outlet, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { ArrowLeft, Server } from 'lucide-react';
import type { AppForm } from '@/components/form';
import { useAppForm, Form } from '@/components/form';
import { httpErrorToHuman } from '@/api/http';
import type { UserValues } from '@/api/admin/users/queries';
import { useAdminLanguages } from '@/api/admin/languages/queries';
import {
    type AdminUserWithServers,
    type AdminUserServer,
    deleteAdminUserInput,
    updateAdminUserInput,
    useAdminUserWithServers,
    useDeleteAdminUser,
    useUpdateAdminUser,
} from '@/api/admin/users/queries';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import UserAvatarCard from './UserAvatarCard';
import { initialExtensionValues, useExtensionPayload } from '@/extensions/forms';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Icon from '@/components/elements/Icon';
import Button from '@/components/elements/Button';
import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';
import { ServerError } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import {
    ExtensionResourceProvider,
    useCurrentResource,
    type ExtensionResourceContext,
} from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';
import { userDetailRoute } from '@/router/routeTree';
import { languageOptions } from '@/components/admin/languageOptions';
import {
    userFormValues,
    validateFirstName,
    validateLastName,
    validateUserEmail,
    validateUsername,
    validateUserPassword,
} from '@/components/admin/users/userForm';
import type { SelectOption } from '@/components/ui/Select';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn, RowActions } from '@/components/elements/table/RowActions';
import ServerEditAction from '@/components/admin/servers/ServerEditAction';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { relationshipData } from '@/api/relationships';
import { resourceNavigation, type ResourceTab } from '@/views/navigation';
import Translate from '@/components/elements/Translate';
import { useTranslation, type Translation } from '@/sdk/localization';

type UserForm = AppForm<UserValues>;

const tabs = (t: Translation['t']): ResourceTab[] => [
    { id: 'about', label: t('About'), to: '/panel/users/$id', exact: true },
];

const IdentityCard = ({
    form,
    languageOptions,
    languagesLoading,
}: {
    form: UserForm;
    languageOptions: SelectOption[];
    languagesLoading: boolean;
}) => {
    const { t } = useTranslation();

    return (
        <TitledGreyBox title={t('Identity')}>
            <form.AppField name='email' validators={{ onChange: validateUserEmail }}>
                {(field) => (
                    <field.TextField
                        type='email'
                        id='email'
                        label={t('Email Address')}
                        description={t('The email address this user will sign in and receive notifications with.')}
                    />
                )}
            </form.AppField>
            <div className='mt-6'>
                <form.AppField name='username' validators={{ onChange: validateUsername }}>
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='username'
                            label={t('Username')}
                            description={t('A unique username used to identify this account.')}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='mt-6 flex flex-wrap'>
                <div className='w-full sm:w-1/2 sm:pr-2'>
                    <form.AppField name='nameFirst' validators={{ onChange: validateFirstName }}>
                        {(field) => <field.TextField type='text' id='name_first' label={t('First Name')} />}
                    </form.AppField>
                </div>
                <div className='w-full mt-6 sm:w-1/2 sm:mt-0 sm:pl-2'>
                    <form.AppField name='nameLast' validators={{ onChange: validateLastName }}>
                        {(field) => <field.TextField type='text' id='name_last' label={t('Last Name')} />}
                    </form.AppField>
                </div>
            </div>
            <div className='mt-6'>
                <form.AppField name='language'>
                    {(field) => (
                        <field.SelectField
                            id='language'
                            label={t('Language')}
                            options={languageOptions}
                            disabled={languagesLoading}
                            placeholder={t('Select a language')}
                            description={t('The default language to use when rendering the Panel.')}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='mt-6'>
                <form.AppField name='rootAdmin'>
                    {(field) => (
                        <field.SwitchField
                            label={t('Administrator')}
                            description={t('Grant this account full administrative access to the Panel.')}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='flex justify-end mt-6'>
                <form.AppForm>
                    <form.SubmitButton>{t('Save Changes')}</form.SubmitButton>
                </form.AppForm>
            </div>
        </TitledGreyBox>
    );
};

const PasswordCard = ({ form }: { form: UserForm }) => {
    const { t } = useTranslation();

    return (
        <TitledGreyBox title={t('Password')}>
            <form.AppField name='password' validators={{ onChange: validateUserPassword }}>
                {(field) => (
                    <field.TextField
                        type='password'
                        id='password'
                        label={t('Password')}
                        description={t(
                            "Leave blank to keep this user's password the same. The user will not receive any notification if their password is changed."
                        )}
                        autoComplete='new-password'
                    />
                )}
            </form.AppField>
            <div className='flex justify-end mt-6'>
                <form.AppForm>
                    <form.SubmitButton>{t('Save Changes')}</form.SubmitButton>
                </form.AppForm>
            </div>
        </TitledGreyBox>
    );
};

const DeleteUserCard = ({
    disabled,
    email,
    onDelete,
}: {
    disabled: boolean;
    email: string;
    onDelete: (close: () => void) => Promise<void>;
}) => {
    const { t } = useTranslation();

    return (
        <TitledGreyBox title={t('Delete User')}>
            <p className='text-sm text-muted-foreground'>
                {t('There must be no servers associated with this account in order for it to be deleted.')}
            </p>
            <div className='flex justify-end mt-6'>
                <Dialog.ConfirmTrigger
                    title={t('Delete user')}
                    confirm={t('Delete User')}
                    trigger={({ onClick }) => (
                        <Button type='button' color='red' disabled={disabled} onClick={onClick}>
                            {t('Delete User')}
                        </Button>
                    )}
                    onConfirmed={(_event, close) => onDelete(close)}
                >
                    <Translate
                        i18nKey='Deleting <bold>{{email}}</bold> is a permanent action and cannot be undone. This will remove the account and all of its associated data.'
                        values={{ email }}
                        components={{ bold: <strong /> }}
                    />
                </Dialog.ConfirmTrigger>
            </div>
        </TitledGreyBox>
    );
};

const ownedServerColumns = (t: Translation['t']) =>
    [
        {
            id: 'name',
            header: t('Server'),
            cell: ({ row }) => (
                <div className='min-w-0'>
                    <Link
                        to='/panel/servers/$id'
                        params={{ id: row.original.attributes.id }}
                        className='block truncate text-sm text-foreground hover:text-accent'
                    >
                        {row.original.attributes.name}
                    </Link>
                    <p className='mt-1 font-mono text-xs text-muted-foreground'>
                        {row.original.attributes.identifier}
                        {row.original.attributes.suspended ? (
                            <span className='ml-2 text-destructive'>{t('suspended')}</span>
                        ) : null}
                    </p>
                </div>
            ),
        },
        {
            id: 'id',
            header: t('ID'),
            cell: ({ row }) => <span className='text-xs text-muted-foreground'>{row.original.attributes.id}</span>,
            meta: { headerClassName: 'w-16 text-right', cellClassName: 'w-16 text-right' },
        },
        actionsColumn<AdminUserServer>(1, (server) => (
            <RowActions>
                <ServerEditAction server={server} />
            </RowActions>
        )),
    ] satisfies ColumnDef<AdminUserServer>[];

const OwnedServersCard = ({ servers }: { servers: AdminUserServer[] }) => {
    const { t } = useTranslation();
    const columns = useMemo(() => ownedServerColumns(t), [t]);
    const table = useReactTable({
        data: servers,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (server) => server.attributes.uuid,
    });

    return (
        <TitledGreyBox title={t('Servers')} className='mt-4'>
            <DataTable
                table={table}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <Server />
                            </EmptyMedia>
                            <EmptyTitle>{t('No servers')}</EmptyTitle>
                            <EmptyDescription>{t("This user doesn't own any servers.")}</EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                }
            />
        </TitledGreyBox>
    );
};

const UserDetailContent = ({ user }: { user: AdminUserWithServers }) => {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const userId = user.attributes.id;

    const updateUser = useUpdateAdminUser();
    const deleteUser = useDeleteAdminUser();

    const { data: languages, isLoading: languagesLoading } = useAdminLanguages();
    const languagesList = languageOptions(languages, user.attributes.language, true);

    const { withExtensionPayload } = useExtensionPayload('admin.user');
    const form = useAppForm({
        defaultValues: userFormValues(user),
        onSubmit: async ({ value }) => {
            try {
                await updateUser.mutateAsync(updateAdminUserInput(userId, withExtensionPayload(value, user)));
                form.reset({ ...value, password: '', extensions: initialExtensionValues() });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const onDelete = (close: () => void) =>
        deleteUser
            .mutateAsync(deleteAdminUserInput(userId, user.attributes.email))
            .then(() => navigate({ to: '/panel/users' }))
            .catch(close);

    const servers = relationshipData(user.attributes.relationships?.servers);
    const ownsServers = servers.length > 0;

    return (
        <>
            <Form form={form} className='m-0'>
                <Slot name='panel.users.detail.form' data={{ kind: 'admin.user', resource: user, form }} />
                <div className='grid grid-cols-1 lg:grid-cols-2 gap-4'>
                    <IdentityCard
                        form={form}
                        languageOptions={languagesList}
                        languagesLoading={languagesLoading && !languages}
                    />

                    <div className='flex flex-col gap-4'>
                        <UserAvatarCard user={user} />
                        <PasswordCard form={form} />
                        <DeleteUserCard disabled={ownsServers} email={user.attributes.email} onDelete={onDelete} />
                    </div>
                </div>
                <form.AppField name='extensions'>
                    {() => (
                        <ExtensionFormFields
                            form='admin.user'
                            mode='edit'
                            resource={user}
                            error={updateUser.error}
                            boxed
                            submitLabel={t('Save Changes')}
                            className='mt-4'
                        />
                    )}
                </form.AppField>
            </Form>

            <OwnedServersCard servers={servers} />
        </>
    );
};

export function UserDetailLayout() {
    const { t } = useTranslation();
    const loadedUser = userDetailRoute.useLoaderData();
    const userId = loadedUser.attributes.id;
    const { data: user = loadedUser, error } = useAdminUserWithServers(userId);

    const resourceContext = useMemo<Extract<ExtensionResourceContext, { kind: 'admin.user' }>>(
        () => ({ kind: 'admin.user', resource: user }),
        [user]
    );

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!user) {
        return (
            <AdminContentBlock title={t('Admin · User')} heading={t('User')}>
                <Spinner size='large' centered />
            </AdminContentBlock>
        );
    }

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={t('Admin · User · {{name}}', { name: user.attributes.email })}
                heading={user.attributes.email}
                description={
                    `${user.attributes.first_name ?? ''} ${user.attributes.last_name ?? ''}`.trim() ||
                    user.attributes.username
                }
            >
                <Link
                    to='/panel/users'
                    className='inline-flex items-center text-sm text-muted-foreground mb-4 hover:text-accent'
                >
                    <Icon icon={ArrowLeft} className='mr-2' />
                    {t('Back to Users')}
                </Link>
                <Slot name='panel.users.detail.actions' data={resourceContext} />
                <SubNavigation
                    className='mb-6 rounded-sm'
                    items={resourceNavigation('admin.user', { id: String(user.attributes.id) }, tabs(t))}
                />
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}

export default function UserDetailContainer() {
    const resource = useCurrentResource();

    if (resource?.kind !== 'admin.user') {
        throw new Error('A user detail tab was rendered without its resource.');
    }

    return <UserDetailContent key={resource.resource.attributes.id} user={resource.resource} />;
}
