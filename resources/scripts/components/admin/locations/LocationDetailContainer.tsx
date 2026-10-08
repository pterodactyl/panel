import { Link, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { ArrowLeft, HardDrive, Trash2 } from 'lucide-react';
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
import { initialExtensionValues, withExtensionPayload } from '@/extensions/forms';
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
import { emptyCompactClass } from '@/components/ui/styles';
import { cardTitleClass } from '@/components/ui/typography';

const locationToValues = (location: AdminLocation): LocationValues => ({
    short: location.attributes.short,
    long: location.attributes.long ?? '',
    extensions: initialExtensionValues(),
});

type LocationNode = Extract<
    NonNullable<NonNullable<AdminLocation['attributes']['relationships']>['nodes']>,
    { data: unknown[] }
>['data'][number];

const locationNodeColumns = [
    {
        id: 'name',
        header: 'Node',
        cell: ({ row }) => (
            <div className={'min-w-0'}>
                <Link
                    to={'/panel/nodes/$id'}
                    params={{ id: row.original.attributes.id }}
                    className={'block truncate text-sm text-foreground hover:text-accent'}
                >
                    {row.original.attributes.name}
                </Link>
                <p className={'mt-1 truncate text-xs text-muted-foreground'}>
                    ID {row.original.attributes.id} · {row.original.attributes.fqdn}
                </p>
            </div>
        ),
    },
    {
        id: 'servers',
        header: 'Servers',
        cell: ({ row }) => (
            <span className={'text-xs text-muted-foreground'}>{row.original.attributes.servers_count}</span>
        ),
        meta: { headerClassName: 'w-20 text-right', cellClassName: 'w-20 text-right' },
    },
    actionsColumn<LocationNode>(1, (node) => (
        <RowActions>
            <EditLinkAction
                aria-label={`Edit ${node.attributes.name}`}
                to={'/panel/nodes/$id/settings'}
                params={{ id: node.attributes.id }}
            />
        </RowActions>
    )),
] satisfies ColumnDef<LocationNode>[];

function LocationDetailForm({ location }: { location: AdminLocation }) {
    const { attributes } = location;
    const nodes = relationshipData(attributes.relationships?.nodes);
    const nodeTable = useReactTable({
        data: nodes,
        columns: locationNodeColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (node) => String(node.attributes.id),
    });
    const navigate = useNavigate();
    const updateLocation = useUpdateAdminLocation();
    const deleteLocation = useDeleteAdminLocation();

    const form = useAppForm({
        defaultValues: locationToValues(location),
        onSubmit: async ({ value }) => {
            try {
                const updated = await updateLocation.mutateAsync(
                    updateAdminLocationInput(attributes.id, withExtensionPayload('admin.location', value, location))
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
                navigate({ to: '/panel/locations' });
            })
            .catch(close);
    };

    return (
        <div className={'grid grid-cols-1 lg:grid-cols-2 gap-4'}>
            <TitledGreyBox title={'Location Details'}>
                <Form form={form} className={'m-0'}>
                    <form.AppField
                        name={'short'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length < 1
                                    ? 'A short code must be provided.'
                                    : value.length <= 60
                                      ? undefined
                                      : 'A short code must not exceed 60 characters.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'short'}
                                label={'Short Code'}
                                description={'A short identifier used to distinguish this location from others.'}
                            />
                        )}
                    </form.AppField>
                    <div className={'mt-6'}>
                        <form.AppField
                            name={'long'}
                            validators={{
                                onChange: ({ value }) =>
                                    (value?.length ?? 0) <= 191
                                        ? undefined
                                        : 'The description must not exceed 191 characters.',
                            }}
                        >
                            {(field) => (
                                <field.TextField
                                    type={'text'}
                                    id={'long'}
                                    label={'Description'}
                                    description={'A longer description of this location.'}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <form.AppField name={'extensions'}>
                        {() => (
                            <ExtensionFormFields
                                form={'admin.location'}
                                mode={'edit'}
                                resource={location}
                                error={updateLocation.error}
                                className={'mt-6'}
                            />
                        )}
                    </form.AppField>
                    <div className={'flex justify-end mt-6'}>
                        <Dialog.ConfirmTrigger
                            title={'Delete location'}
                            confirm={'Delete Location'}
                            pending={deleteLocation.isPending}
                            onConfirmed={(_event, close) => onDelete(close)}
                            trigger={({ onClick }) => (
                                <Button type={'button'} color={'red'} isSecondary className={'mr-2'} onClick={onClick}>
                                    <Icon icon={Trash2} className={'mr-2'} />
                                    Delete Location
                                </Button>
                            )}
                        >
                            Deleting <strong>{attributes.short}</strong> is permanent and cannot be undone. Locations
                            with assigned nodes cannot be deleted.
                        </Dialog.ConfirmTrigger>
                        <form.AppForm>
                            <form.SubmitButton>Save Changes</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </Form>
            </TitledGreyBox>

            <TitledGreyBox
                title={
                    <div className={'flex items-center justify-between gap-3'}>
                        <h2 className={cardTitleClass}>Nodes</h2>
                        <span className={'text-xs text-muted-foreground'}>
                            {attributes.nodes_count} nodes · {attributes.servers_count} servers
                        </span>
                    </div>
                }
            >
                <DataTable
                    table={nodeTable}
                    emptyState={
                        <Empty className={emptyCompactClass}>
                            <EmptyHeader>
                                <EmptyMedia variant={'icon'}>
                                    <HardDrive />
                                </EmptyMedia>
                                <EmptyTitle>No nodes</EmptyTitle>
                                <EmptyDescription>Nodes assigned to this location will appear here.</EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            </TitledGreyBox>
        </div>
    );
}

export default function LocationDetailContainer() {
    const loadedLocation = locationDetailRoute.useLoaderData();
    const locationId = loadedLocation.attributes.id;

    const { data: location = loadedLocation, error } = useAdminLocation(locationId);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!location) {
        return (
            <AdminContentBlock title={'Admin · Location'} heading={'Location'}>
                <Spinner size={'large'} centered />
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock
            title={`Admin · Location · ${location.attributes.short}`}
            heading={location.attributes.short}
            description={location.attributes.long ?? 'No description provided.'}
        >
            <Link
                to={'/panel/locations'}
                className={'inline-flex items-center text-sm text-muted-foreground hover:text-accent mb-4'}
            >
                <Icon icon={ArrowLeft} className={'mr-2'} />
                Back to Locations
            </Link>

            <LocationDetailForm
                key={`${location.attributes.id}:${location.attributes.updated_at}`}
                location={location}
            />
        </AdminContentBlock>
    );
}
