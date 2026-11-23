import { Link, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { ArrowLeft, HardDrive, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import { useAppForm, Form } from '@/components/form';
import { httpErrorToHuman } from '@/api/http';
import type { AdminLocation } from '@/api/admin/locations/queries';
import {
    deleteAdminLocationInput,
    type LocationValues,
    updateAdminLocationInput,
    useAdminLocation,
    useDeleteAdminLocation,
    useUpdateAdminLocation,
} from '@/api/admin/locations/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, useExtensionPayload } from '@/extensions/forms';
import { validateShortCode } from '@/components/admin/locations/locationForm';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Icon from '@/components/elements/Icon';
import Button from '@/components/elements/Button';
import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';
import { ServerError } from '@/components/elements/ScreenBlock';
import { locationDetailRoute } from '@/router/routeTree';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { relationshipData } from '@/api/relationships';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { cardTitleClass } from '@/components/ui/typography';
import Translate from '@/components/elements/Translate';
import { useTranslation, type Translation } from '@/sdk/localization';

const locationToValues = (location: AdminLocation): LocationValues => ({
    short: location.attributes.short,
    long: location.attributes.long ?? '',
    extensions: initialExtensionValues(),
});

type LocationNode = Extract<
    NonNullable<NonNullable<AdminLocation['attributes']['relationships']>['nodes']>,
    { data: unknown[] }
>['data'][number];

const locationNodeColumns = (t: Translation['t']) =>
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
                        {t('ID {{id}} · {{fqdn}}', {
                            id: row.original.attributes.id,
                            fqdn: row.original.attributes.fqdn,
                        })}
                    </p>
                </div>
            ),
        },
        {
            id: 'servers',
            header: t('Servers'),
            cell: ({ row }) => (
                <span className='text-xs text-muted-foreground'>{row.original.attributes.servers_count}</span>
            ),
            meta: { headerClassName: 'w-20 text-right', cellClassName: 'w-20 text-right' },
        },
        actionsColumn<LocationNode>(1, (node) => (
            <RowActions>
                <EditLinkAction
                    aria-label={t('Edit {{name}}', { name: node.attributes.name })}
                    to='/panel/nodes/$id/settings'
                    params={{ id: node.attributes.id }}
                />
            </RowActions>
        )),
    ] satisfies ColumnDef<LocationNode>[];

function LocationDetailForm({ location }: { location: AdminLocation }) {
    const { t } = useTranslation();
    const { attributes } = location;
    const nodes = relationshipData(attributes.relationships?.nodes);
    const columns = useMemo(() => locationNodeColumns(t), [t]);
    const nodeTable = useReactTable({
        data: nodes,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (node) => String(node.attributes.id),
    });
    const navigate = useNavigate();
    const updateLocation = useUpdateAdminLocation();
    const deleteLocation = useDeleteAdminLocation();

    const { withExtensionPayload } = useExtensionPayload('admin.location');
    const form = useAppForm({
        defaultValues: locationToValues(location),
        onSubmit: async ({ value }) => {
            try {
                const updated = await updateLocation.mutateAsync(
                    updateAdminLocationInput(attributes.id, withExtensionPayload(value, location))
                );

                form.reset(locationToValues(updated));
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const onDelete = (close: () => void) => {
        deleteLocation
            .mutateAsync(deleteAdminLocationInput(attributes.id, attributes.short))
            .then(() => {
                void navigate({ to: '/panel/locations' });
            })
            .catch(close);
    };

    return (
        <div className='grid grid-cols-1 lg:grid-cols-2 gap-4'>
            <TitledGreyBox title={t('Location Details')}>
                <Form form={form} className='m-0'>
                    <form.AppField
                        name='short'
                        validators={{
                            onChange: ({ value }) => validateShortCode(value),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='short'
                                label={t('Short Code')}
                                description={t('A short identifier used to distinguish this location from others.')}
                            />
                        )}
                    </form.AppField>
                    <div className='mt-6'>
                        <form.AppField
                            name='long'
                            validators={{
                                onChange: ({ value }) =>
                                    (value?.length ?? 0) <= 191
                                        ? undefined
                                        : t('The description must not exceed 191 characters.'),
                            }}
                        >
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='long'
                                    label={t('Description')}
                                    description={t('A longer description of this location.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <form.AppField name='extensions'>
                        {() => (
                            <ExtensionFormFields
                                form='admin.location'
                                mode='edit'
                                resource={location}
                                error={updateLocation.error}
                                className='mt-6'
                            />
                        )}
                    </form.AppField>
                    <div className='flex justify-end mt-6'>
                        <Dialog.ConfirmTrigger
                            title={t('Delete location')}
                            confirm={t('Delete Location')}
                            pending={deleteLocation.isPending}
                            onConfirmed={(_event, close) => onDelete(close)}
                            trigger={({ onClick }) => (
                                <Button type='button' color='red' isSecondary className='mr-2' onClick={onClick}>
                                    <Icon icon={Trash2} />
                                    {t('Delete Location')}
                                </Button>
                            )}
                        >
                            <Translate
                                i18nKey='Deleting <bold>{{name}}</bold> is permanent and cannot be undone. Locations with assigned nodes cannot be deleted.'
                                values={{ name: attributes.short }}
                                components={{ bold: <strong /> }}
                            />
                        </Dialog.ConfirmTrigger>
                        <form.AppForm>
                            <form.SubmitButton>{t('Save Changes')}</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </Form>
            </TitledGreyBox>

            <TitledGreyBox
                title={
                    <div className='flex items-center justify-between gap-3'>
                        <h2 className={cardTitleClass}>{t('Nodes')}</h2>
                        <span className='text-xs text-muted-foreground'>
                            {t('{{nodes}} nodes · {{servers}} servers', {
                                nodes: attributes.nodes_count,
                                servers: attributes.servers_count,
                            })}
                        </span>
                    </div>
                }
            >
                <DataTable
                    table={nodeTable}
                    emptyState={
                        <Empty size='compact'>
                            <EmptyHeader>
                                <EmptyMedia variant='icon'>
                                    <HardDrive />
                                </EmptyMedia>
                                <EmptyTitle>{t('No nodes')}</EmptyTitle>
                                <EmptyDescription>
                                    {t('Nodes assigned to this location will appear here.')}
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            </TitledGreyBox>
        </div>
    );
}

export default function LocationDetailContainer() {
    const { t } = useTranslation();
    const loadedLocation = locationDetailRoute.useLoaderData();
    const locationId = loadedLocation.attributes.id;

    const { data: location = loadedLocation, error } = useAdminLocation(locationId);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!location) {
        return (
            <AdminContentBlock title={t('Admin · Location')} heading={t('Location')}>
                <Spinner size='large' centered />
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock
            title={t('Admin · Location · {{name}}', { name: location.attributes.short })}
            heading={location.attributes.short}
            description={location.attributes.long ?? t('No description provided.')}
        >
            <Link
                to='/panel/locations'
                className='inline-flex items-center text-sm text-muted-foreground hover:text-accent mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                {t('Back to Locations')}
            </Link>

            <LocationDetailForm
                key={`${location.attributes.id}:${location.attributes.updated_at}`}
                location={location}
            />
        </AdminContentBlock>
    );
}
