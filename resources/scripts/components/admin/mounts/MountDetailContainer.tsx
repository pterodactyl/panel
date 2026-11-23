import { useMemo } from 'react';
import { useStore } from '@tanstack/react-form';
import { Link, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { ArrowLeft, Egg, HardDrive } from 'lucide-react';
import type { AppForm } from '@/components/form';
import { useAppForm, Form } from '@/components/form';
import { httpErrorToHuman } from '@/api/http';
import {
    type AdminMountEgg,
    type AdminMountNode,
    type AdminMountWithRelations,
    deleteAdminMountInput,
    detachAdminMountEggInput,
    detachAdminMountNodeInput,
    type MountValues,
    updateAdminMountInput,
    useAdminMount,
    useDeleteAdminMount,
    useDetachAdminMountEgg,
    useDetachAdminMountNode,
    useUpdateAdminMount,
} from '@/api/admin/mounts/queries';
import { mountDetailRoute } from '@/router/routeTree';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, useExtensionPayload } from '@/extensions/forms';
import AttachEggsModal from '@/components/admin/mounts/AttachEggsModal';
import AttachNodesModal from '@/components/admin/mounts/AttachNodesModal';
import { validateMountName } from '@/components/admin/mounts/mountForm';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Icon from '@/components/elements/Icon';
import Button from '@/components/elements/Button';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn, DeleteAction, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewButton } from '@/components/elements/NewButton';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { relationshipData } from '@/api/relationships';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

type MountForm = AppForm<MountValues>;

const mountToValues = (mount: AdminMountWithRelations): MountValues => ({
    name: mount.attributes.name,
    description: mount.attributes.description ?? '',
    source: mount.attributes.source,
    target: mount.attributes.target,
    readOnly: mount.attributes.read_only,
    userMountable: mount.attributes.user_mountable,
    extensions: initialExtensionValues(),
});

const MountDetailsCard = ({
    form,
    mount,
    error,
    onDelete,
}: {
    form: MountForm;
    mount: AdminMountWithRelations;
    error: unknown;
    onDelete: () => void;
}) => {
    const { t } = useTranslation();

    return (
        <TitledGreyBox title={t('Mount Details')}>
            <Form form={form} className='m-0'>
                <form.AppField
                    name='name'
                    validators={{
                        onChange: ({ value }) => validateMountName(value),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='name'
                            label={t('Name')}
                            description={t('A unique name used to identify this mount.')}
                        />
                    )}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField
                        name='description'
                        validators={{
                            onChange: ({ value }) =>
                                value.length <= 191 ? undefined : t('A description must not exceed 191 characters.'),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='description'
                                label={t('Description')}
                                description={t('A longer description for this mount.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField
                        name='source'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : t('A source path must be provided.'),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='source'
                                label={t('Source')}
                                description={t('The path on the host system to mount into the container.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField
                        name='target'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : t('A target path must be provided.'),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='target'
                                label={t('Target')}
                                description={t('The path inside the container where the source will be mounted.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='readOnly'>
                        {(field) => (
                            <field.SwitchField
                                label={t('Read Only')}
                                description={t('Mount this volume as read only inside the container.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='userMountable'>
                        {(field) => (
                            <field.SwitchField
                                label={t('User Mountable')}
                                description={t('Allow this mount to be added to servers by users.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <form.AppField name='extensions'>
                    {() => (
                        <ExtensionFormFields
                            form='admin.mount'
                            mode='edit'
                            resource={mount}
                            error={error}
                            className='mt-6'
                        />
                    )}
                </form.AppField>
                <div className='flex justify-end mt-6'>
                    <Button type='button' color='red' isSecondary className='mr-2' onClick={onDelete}>
                        {t('Delete Mount')}
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton>{t('Save Changes')}</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </TitledGreyBox>
    );
};

const AttachedEggsCard = ({
    eggs,
    onDetach,
    onAttach,
}: {
    eggs: AdminMountEgg[];
    onDetach: (egg: AdminMountEgg) => void;
    onAttach: () => void;
}) => {
    const { t } = useTranslation();
    const columns = useMemo(
        () =>
            [
                {
                    id: 'name',
                    header: t('Egg'),
                    cell: ({ row }) => (
                        <Link
                            to='/panel/eggs/$eggId'
                            params={{ eggId: row.original.attributes.id }}
                            className='block truncate text-sm text-foreground hover:text-accent'
                        >
                            {row.original.attributes.name}
                        </Link>
                    ),
                },
                {
                    id: 'id',
                    header: t('ID'),
                    cell: ({ row }) => (
                        <span className='text-xs text-muted-foreground'>{row.original.attributes.id}</span>
                    ),
                    meta: {
                        headerClassName: 'hidden sm:table-cell w-16 text-right',
                        cellClassName: 'hidden sm:table-cell w-16 text-right',
                    },
                },
                actionsColumn<AdminMountEgg>(2, (egg) => (
                    <RowActions>
                        <EditLinkAction
                            aria-label={t('Edit {{name}}', { name: egg.attributes.name })}
                            to='/panel/eggs/$eggId'
                            params={{ eggId: egg.attributes.id }}
                        />
                        <Dialog.ConfirmTrigger
                            title={t('Detach egg')}
                            confirm={t('Detach Egg')}
                            trigger={({ onClick }) => (
                                <DeleteAction
                                    label={t('Detach')}
                                    aria-label={t('Detach {{name}}', { name: egg.attributes.name })}
                                    onClick={onClick}
                                />
                            )}
                            onConfirmed={(_event, close) => {
                                close();
                                onDetach(egg);
                            }}
                        >
                            <Translate
                                i18nKey='This will detach <bold>{{name}}</bold> from this mount. Servers using that egg will no longer be offered this mount.'
                                values={{ name: egg.attributes.name }}
                                components={{ bold: <strong /> }}
                            />
                        </Dialog.ConfirmTrigger>
                    </RowActions>
                )),
            ] satisfies ColumnDef<AdminMountEgg>[],
        [t, onDetach]
    );
    const table = useReactTable({
        data: eggs,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (egg) => String(egg.attributes.id),
    });

    return (
        <TitledGreyBox title={t('Eggs')}>
            <ListToolbar>
                <NewButton onClick={onAttach}>{t('Attach eggs')}</NewButton>
            </ListToolbar>
            <DataTable
                table={table}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <Egg />
                            </EmptyMedia>
                            <EmptyTitle>{t('No eggs attached')}</EmptyTitle>
                            <EmptyDescription>
                                {t('Servers using an attached egg are offered this mount.')}
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <NewButton onClick={onAttach}>{t('Attach eggs')}</NewButton>
                        </EmptyContent>
                    </Empty>
                }
            />
        </TitledGreyBox>
    );
};

const AttachedNodesCard = ({
    nodes,
    onDetach,
    onAttach,
}: {
    nodes: AdminMountNode[];
    onDetach: (node: AdminMountNode) => void;
    onAttach: () => void;
}) => {
    const { t } = useTranslation();
    const columns = useMemo(
        () =>
            [
                {
                    id: 'name',
                    header: t('Node'),
                    cell: ({ row }) => (
                        <div className='min-w-0'>
                            <Link
                                to='/panel/nodes/$id'
                                params={{ id: row.original.attributes.id }}
                                className='block truncate text-sm text-foreground hover:text-accent'
                            >
                                {row.original.attributes.name}
                            </Link>
                            <p className='mt-1 truncate text-xs text-muted-foreground'>
                                {row.original.attributes.fqdn}
                            </p>
                        </div>
                    ),
                },
                actionsColumn<AdminMountNode>(2, (node) => (
                    <RowActions>
                        <EditLinkAction
                            aria-label={t('Edit {{name}}', { name: node.attributes.name })}
                            to='/panel/nodes/$id/settings'
                            params={{ id: node.attributes.id }}
                        />
                        <Dialog.ConfirmTrigger
                            title={t('Detach node')}
                            confirm={t('Detach Node')}
                            trigger={({ onClick }) => (
                                <DeleteAction
                                    label={t('Detach')}
                                    aria-label={t('Detach {{name}}', { name: node.attributes.name })}
                                    onClick={onClick}
                                />
                            )}
                            onConfirmed={(_event, close) => {
                                close();
                                onDetach(node);
                            }}
                        >
                            <Translate
                                i18nKey='This will detach <bold>{{name}}</bold> from this mount. Servers on that node will no longer be offered this mount.'
                                values={{ name: node.attributes.name }}
                                components={{ bold: <strong /> }}
                            />
                        </Dialog.ConfirmTrigger>
                    </RowActions>
                )),
            ] satisfies ColumnDef<AdminMountNode>[],
        [t, onDetach]
    );
    const table = useReactTable({
        data: nodes,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (node) => String(node.attributes.id),
    });

    return (
        <TitledGreyBox title={t('Nodes')}>
            <ListToolbar>
                <NewButton onClick={onAttach}>{t('Attach nodes')}</NewButton>
            </ListToolbar>
            <DataTable
                table={table}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <HardDrive />
                            </EmptyMedia>
                            <EmptyTitle>{t('No nodes attached')}</EmptyTitle>
                            <EmptyDescription>
                                {t('Servers on an attached node are offered this mount.')}
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <NewButton onClick={onAttach}>{t('Attach nodes')}</NewButton>
                        </EmptyContent>
                    </Empty>
                }
            />
        </TitledGreyBox>
    );
};

const DeleteMountDialog = ({
    mount,
    onClose,
    onDeleted,
}: {
    mount: AdminMountWithRelations;
    onClose: () => void;
    onDeleted: () => Promise<void>;
}) => {
    const { t } = useTranslation();
    const deleteMount = useDeleteAdminMount();
    const deleteForm = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteMount.mutateAsync(deleteAdminMountInput(mount.attributes.id, mount.attributes.name));
                await onDeleted();
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const deleteSubmitting = useStore(deleteForm.store, (state) => state.isSubmitting);

    const close = () => {
        deleteForm.reset();
        onClose();
    };

    return (
        <Dialog
            open
            title={t('Confirm mount deletion')}
            preventExternalClose={deleteSubmitting}
            hideCloseIcon={deleteSubmitting}
            onClose={close}
        >
            <SpinnerOverlay visible={deleteSubmitting} />
            <p className='text-sm'>
                <Translate
                    i18nKey='Deleting a mount is a permanent action, it cannot be undone. This will permanently delete the <bold>{{name}}</bold> mount and detach it from every egg and node.'
                    values={{ name: mount.attributes.name }}
                    components={{ bold: <strong /> }}
                />
            </p>
            <Form form={deleteForm} className='m-0 mt-6'>
                <deleteForm.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === mount.attributes.name ? undefined : t('The mount name must be provided.'),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='confirm_mount_name'
                            label={t('Confirm Name')}
                            description={t('Enter the name of this mount to confirm deletion.')}
                        />
                    )}
                </deleteForm.AppField>
                <div className='mt-6 text-right'>
                    <Button type='button' isSecondary className='mr-2' onClick={close}>
                        {t('Cancel')}
                    </Button>
                    <deleteForm.AppForm>
                        <deleteForm.SubmitButton color='red'>{t('Delete Mount')}</deleteForm.SubmitButton>
                    </deleteForm.AppForm>
                </div>
            </Form>
        </Dialog>
    );
};

const MountDetailContent = ({ mount }: { mount: AdminMountWithRelations }) => {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const mountId = mount.attributes.id;

    const updateMount = useUpdateAdminMount();
    const detachMountEgg = useDetachAdminMountEgg();
    const detachMountNode = useDetachAdminMountNode();

    const { withExtensionPayload } = useExtensionPayload('admin.mount');
    const editForm = useAppForm({
        defaultValues: mountToValues(mount),
        onSubmit: async ({ value }) => {
            try {
                await updateMount.mutateAsync(updateAdminMountInput(mountId, withExtensionPayload(value, mount)));
                editForm.reset({ ...value, extensions: initialExtensionValues() });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const onDetachEgg = (egg: AdminMountEgg) => {
        detachMountEgg.mutate(detachAdminMountEggInput(mountId, egg.attributes.id, egg.attributes.name));
    };

    const onDetachNode = (node: AdminMountNode) => {
        detachMountNode.mutate(detachAdminMountNodeInput(mountId, node.attributes.id, node.attributes.name));
    };

    const { attributes } = mount;

    return (
        <AdminContentBlock
            title={t('Admin · Mount · {{name}}', { name: attributes.name })}
            heading={attributes.name}
            description={attributes.uuid}
        >
            <Link
                to='/panel/mounts'
                className='inline-flex items-center text-sm text-muted-foreground mb-4 hover:text-foreground'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                {t('Back to Mounts')}
            </Link>

            <div className='grid grid-cols-1 lg:grid-cols-2 gap-4'>
                <Dialog.Trigger
                    trigger={({ onClick }) => (
                        <MountDetailsCard form={editForm} mount={mount} error={updateMount.error} onDelete={onClick} />
                    )}
                >
                    {({ open, onClose }) =>
                        open && (
                            <DeleteMountDialog
                                mount={mount}
                                onClose={onClose}
                                onDeleted={() => navigate({ to: '/panel/mounts' })}
                            />
                        )
                    }
                </Dialog.Trigger>
                <div className='flex flex-col gap-4'>
                    <Dialog.Trigger
                        trigger={({ onClick }) => (
                            <AttachedEggsCard
                                eggs={relationshipData(mount.attributes.relationships?.eggs)}
                                onDetach={onDetachEgg}
                                onAttach={onClick}
                            />
                        )}
                    >
                        {({ open, onClose }) =>
                            open && (
                                <AttachEggsModal
                                    key={`${attributes.id}:attach-eggs`}
                                    mount={mount}
                                    open={open}
                                    onClose={onClose}
                                    onAttached={onClose}
                                />
                            )
                        }
                    </Dialog.Trigger>
                    <Dialog.Trigger
                        trigger={({ onClick }) => (
                            <AttachedNodesCard
                                nodes={relationshipData(mount.attributes.relationships?.nodes)}
                                onDetach={onDetachNode}
                                onAttach={onClick}
                            />
                        )}
                    >
                        {({ open, onClose }) =>
                            open && (
                                <AttachNodesModal
                                    key={`${attributes.id}:attach-nodes`}
                                    mount={mount}
                                    open={open}
                                    onClose={onClose}
                                    onAttached={onClose}
                                />
                            )
                        }
                    </Dialog.Trigger>
                </div>
            </div>
        </AdminContentBlock>
    );
};

export default function MountDetailContainer() {
    const { t } = useTranslation();
    const loadedMount = mountDetailRoute.useLoaderData();
    const mountId = loadedMount.attributes.id;
    const { data: mount = loadedMount, error } = useAdminMount(mountId);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!mount) {
        return (
            <AdminContentBlock title={t('Admin · Mount')} heading={t('Mount')}>
                <Spinner size='large' centered />
            </AdminContentBlock>
        );
    }

    return <MountDetailContent key={mount.attributes.id} mount={mount} />;
}
