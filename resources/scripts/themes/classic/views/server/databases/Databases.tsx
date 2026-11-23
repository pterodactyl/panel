import { useMemo } from 'react';
import { Database, Eye } from 'lucide-react';
import {
    actionsColumn,
    Button,
    CopyOnClick,
    DeleteAction,
    Dialog,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Form,
    Input,
    Label,
    ListToolbar,
    NewButton,
    RowActionButton,
    RowActions,
    SecretInput,
    Spinner,
    SpinnerOverlay,
    Table,
    Translate,
    useAppForm,
    useTranslation,
    type TableColumn,
    type Translation,
} from '@/sdk';
import type { ServerDatabaseItem, ServerDatabasesModel, ViewProps } from '@/sdk/theme';

interface DatabaseDialogProps {
    model: ServerDatabasesModel;
    database: ServerDatabaseItem;
    open: boolean;
    onClose: () => void;
}

function CreateDatabaseDialog({ model, onClose }: { model: ServerDatabasesModel; onClose: () => void }) {
    const { t } = useTranslation();
    const form = useAppForm({
        defaultValues: { databaseName: '', connectionsFrom: '' },
        onSubmit: async ({ value }) => {
            try {
                await model.actions.create({ name: value.databaseName, connectionsFrom: value.connectionsFrom });
                onClose();
            } catch {
                // The failure has already been reported.
            }
        },
    });

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(isSubmitting) => (
                <Dialog
                    open
                    title={t('Create new database')}
                    preventExternalClose={isSubmitting}
                    hideCloseIcon={isSubmitting}
                    onClose={() => {
                        form.reset();
                        onClose();
                    }}
                >
                    <SpinnerOverlay visible={isSubmitting} />
                    <Form form={form} className='m-0'>
                        <form.AppField
                            name='databaseName'
                            validators={{ onChange: ({ value }) => model.validate.name(value) }}
                        >
                            {(field) => (
                                <field.TextField
                                    type='string'
                                    id='database_name'
                                    label={t('Database Name')}
                                    description={t('A descriptive name for your database instance.')}
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6'>
                            <form.AppField
                                name='connectionsFrom'
                                validators={{ onChange: ({ value }) => model.validate.connectionsFrom(value) }}
                            >
                                {(field) => (
                                    <field.TextField
                                        type='string'
                                        id='connections_from'
                                        label={t('Connections From')}
                                        description={t(
                                            'Where connections should be allowed from. Leave blank to allow connections from anywhere.'
                                        )}
                                    />
                                )}
                            </form.AppField>
                        </div>
                        <div className='flex flex-wrap justify-end mt-6'>
                            <Button type='button' isSecondary className='w-full sm:w-auto sm:mr-2' onClick={onClose}>
                                {t('Cancel')}
                            </Button>
                            <form.AppForm>
                                <form.SubmitButton className='w-full mt-4 sm:w-auto sm:mt-0'>
                                    {t('Create Database')}
                                </form.SubmitButton>
                            </form.AppForm>
                        </div>
                    </Form>
                </Dialog>
            )}
        </form.Subscribe>
    );
}

function CreateDatabaseButton({ model }: { model: ServerDatabasesModel }) {
    const { t } = useTranslation();

    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>{t('New database')}</NewButton>}>
            {({ open, onClose }) => open && <CreateDatabaseDialog model={model} onClose={onClose} />}
        </Dialog.Trigger>
    );
}

function DeleteDatabaseDialog({ model, database, open, onClose }: DatabaseDialogProps) {
    const { t } = useTranslation();
    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await model.actions.remove(database.id);
                onClose();
                form.reset();
            } catch {
                // The failure has already been reported.
            }
        },
    });
    const close = () => {
        onClose();
        form.reset();
    };

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(isSubmitting) => (
                <Dialog
                    open={open}
                    title={t('Confirm database deletion')}
                    preventExternalClose={isSubmitting}
                    hideCloseIcon={isSubmitting}
                    onClose={close}
                >
                    <SpinnerOverlay visible={isSubmitting} />
                    <p className='text-sm'>
                        <Translate
                            i18nKey='Deleting a database is permanent. This will delete <bold>{{name}}</bold> and all associated data.'
                            values={{ name: database.name }}
                            components={{ bold: <strong /> }}
                        />
                    </p>
                    <Form form={form} className='m-0 mt-6'>
                        <form.AppField
                            name='confirm'
                            validators={{
                                onChange: ({ value }) =>
                                    value === database.name || value === database.shortName
                                        ? undefined
                                        : t('The database name must be provided.'),
                            }}
                        >
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='confirm_name'
                                    label={t('Confirm Database Name')}
                                    description={t('Enter the database name to confirm deletion.')}
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6 text-right'>
                            <Button type='button' isSecondary className='mr-2' onClick={close}>
                                {t('Cancel')}
                            </Button>
                            <form.AppForm>
                                <form.SubmitButton color='red'>{t('Delete Database')}</form.SubmitButton>
                            </form.AppForm>
                        </div>
                    </Form>
                </Dialog>
            )}
        </form.Subscribe>
    );
}

function ConnectionDialog({ model, database, open, onClose }: DatabaseDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} title={t('Database connection details')} onClose={onClose}>
            <div>
                <Label>{t('Endpoint')}</Label>
                <CopyOnClick text={database.endpoint}>
                    <Input type='text' readOnly value={database.endpoint} />
                </CopyOnClick>
            </div>
            <div className='mt-6'>
                <Label>{t('Connections from')}</Label>
                <Input type='text' readOnly value={database.connectionsFrom} />
            </div>
            <div className='mt-6'>
                <Label>{t('Username')}</Label>
                <CopyOnClick text={database.username}>
                    <Input type='text' readOnly value={database.username} />
                </CopyOnClick>
            </div>
            {model.permissions.viewPassword && (
                <div className='mt-6'>
                    <Label>{t('Password')}</Label>
                    <SecretInput value={database.password} label={t('Password')} />
                </div>
            )}
            <div className='mt-6'>
                <Label>{t('JDBC Connection String')}</Label>
                <CopyOnClick text={database.jdbc} showInNotification={false}>
                    <Input type='text' readOnly value={database.jdbc} />
                </CopyOnClick>
            </div>
            <div className='mt-6 flex justify-end gap-2'>
                {model.permissions.update && (
                    <Button
                        isSecondary
                        color='primary'
                        className='mr-2'
                        onClick={() => void model.actions.rotatePassword(database.id).catch(() => {})}
                        isLoading={model.rotating.includes(database.id)}
                    >
                        {t('Rotate Password')}
                    </Button>
                )}
                <Button isSecondary onClick={onClose}>
                    {t('Close')}
                </Button>
            </div>
        </Dialog>
    );
}

function DatabaseActions({ model, database }: { model: ServerDatabasesModel; database: ServerDatabaseItem }) {
    const { t } = useTranslation();

    return (
        <RowActions>
            <Dialog.Trigger
                trigger={({ onClick }) => (
                    <RowActionButton
                        icon={Eye}
                        label={t('Connection details')}
                        aria-label={t('View {{name}} connection details', { name: database.name })}
                        onClick={onClick}
                    />
                )}
            >
                {({ open, onClose }) => (
                    <ConnectionDialog model={model} database={database} open={open} onClose={onClose} />
                )}
            </Dialog.Trigger>
            {model.permissions.delete && (
                <Dialog.Trigger
                    trigger={({ onClick }) => (
                        <DeleteAction aria-label={t('Delete {{name}}', { name: database.name })} onClick={onClick} />
                    )}
                >
                    {({ open, onClose }) => (
                        <DeleteDatabaseDialog model={model} database={database} open={open} onClose={onClose} />
                    )}
                </Dialog.Trigger>
            )}
        </RowActions>
    );
}

function databaseColumns(model: ServerDatabasesModel, t: Translation['t']): TableColumn<ServerDatabaseItem>[] {
    return [
        {
            id: 'name',
            label: t('Database'),
            sortValue: (database) => database.name,
            cell: (database) => (
                <CopyOnClick text={database.name}>
                    <span className='font-medium'>{database.name}</span>
                </CopyOnClick>
            ),
        },
        {
            id: 'endpoint',
            label: t('Endpoint'),
            sortValue: (database) => database.endpoint,
            cell: (database) => (
                <CopyOnClick text={database.endpoint}>
                    <code className='whitespace-nowrap text-xs'>{database.endpoint}</code>
                </CopyOnClick>
            ),
            headerClassName: 'hidden md:table-cell',
            cellClassName: 'hidden md:table-cell',
        },
        {
            id: 'connections_from',
            label: t('Connections from'),
            sortValue: (database) => database.connectionsFrom,
            cell: (database) => database.connectionsFrom,
            headerClassName: 'hidden lg:table-cell',
            cellClassName: 'hidden lg:table-cell',
        },
        {
            id: 'username',
            label: t('Username'),
            sortValue: (database) => database.username,
            cell: (database) => (
                <CopyOnClick text={database.username}>
                    <code className='text-xs'>{database.username}</code>
                </CopyOnClick>
            ),
            headerClassName: 'hidden xl:table-cell',
            cellClassName: 'hidden xl:table-cell',
        },
        actionsColumn(2, (database) => <DatabaseActions model={model} database={database} />),
    ];
}

function EmptyState({ model }: { model: ServerDatabasesModel }) {
    const { t } = useTranslation();

    return (
        <Empty size='compact'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Database />
                </EmptyMedia>
                <EmptyTitle>{t('No databases')}</EmptyTitle>
                <EmptyDescription>
                    {model.limit > 0
                        ? t("This server doesn't have any databases yet.")
                        : t("Databases can't be created because this server's database limit is 0.")}
                </EmptyDescription>
            </EmptyHeader>
            {model.canCreate && (
                <EmptyContent>
                    <CreateDatabaseButton model={model} />
                </EmptyContent>
            )}
        </Empty>
    );
}

export default function Databases({ model, className }: ViewProps<'server.databases'>) {
    const { t } = useTranslation();
    const columns = useMemo(() => databaseColumns(model, t), [model, t]);
    const count = model.databases.length;
    const usage =
        model.permissions.create && model.limit > 0 && count > 0
            ? t('{{allocated}} of {{limit}} databases have been allocated to this server.', {
                  allocated: count,
                  limit: model.limit,
              })
            : null;

    if (model.loading) {
        return (
            <div className={className}>
                <Spinner size='large' centered />
            </div>
        );
    }

    return (
        <div className={className}>
            {(usage || model.canCreate) && (
                <ListToolbar summary={usage}>{model.canCreate && <CreateDatabaseButton model={model} />}</ListToolbar>
            )}
            <Table
                rows={model.databases}
                columns={columns}
                keyOf={(database) => database.id}
                emptyState={<EmptyState model={model} />}
            />
        </div>
    );
}
