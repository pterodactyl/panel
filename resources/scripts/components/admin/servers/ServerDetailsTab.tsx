import { useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import {
    type AdminServer,
    updateAdminServerDetailsInput,
    useUpdateAdminServerDetails,
} from '@/api/admin/servers/queries';
import { serverDetailsBodyFromFormValues } from '@/components/admin/servers/helpers';
import { useAdminUsers } from '@/api/admin/users/queries';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, useExtensionPayload } from '@/extensions/forms';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerError } from '@/components/elements/ScreenBlock';
import { relationshipAttributes } from '@/api/relationships';
import { useDebouncedValue } from '@/plugins/useDebouncedValue';
import type { SelectOption } from '@/components/ui/Select';

interface Props {
    server: AdminServer;
}

type OwnerOption = {
    id: number;
    email: string;
    name: string;
    image: string;
};

interface OwnerUser {
    id: number;
    email: string;
    image: string;
    first_name: string;
    last_name: string;
    username: string;
}

const ownerOption = (user: OwnerUser): OwnerOption => ({
    id: user.id,
    email: user.email,
    name: [user.first_name, user.last_name].filter(Boolean).join(' ') || user.username,
    image: user.image,
});

const ownerSelectOption = (user: OwnerOption): SelectOption => ({
    value: user.id,
    textLabel: `${user.name} (${user.email})`,
    label: (
        <span className='flex min-w-0 items-center gap-3'>
            <img
                className='h-8 w-8 shrink-0 rounded-full border border-border bg-background'
                src={`${user.image}?s=100`}
                alt=''
            />
            <span className='min-w-0'>
                <span className='block truncate'>{user.name}</span>
                <span className='block truncate text-xs text-muted-foreground'>{user.email}</span>
            </span>
        </span>
    ),
});

function ServerDetailsForm({ server }: Props) {
    const { attributes } = server;
    const [search, setSearch] = useState('');
    const [pickedOwner, setPickedOwner] = useState<OwnerOption | null>(null);
    const updateServerDetails = useUpdateAdminServerDetails();

    const debouncedSearch = useDebouncedValue(search, 500);
    const { data: searchResult, isFetching: isSearching } = useAdminUsers({
        page: 1,
        filters: { search: debouncedSearch.trim() },
    });

    const { extensionFormPayload } = useExtensionPayload('admin.server');
    const form = useAppForm({
        defaultValues: {
            name: attributes.name,
            user: attributes.user,
            externalId: attributes.external_id ?? '',
            description: attributes.description ?? '',
            extensions: initialExtensionValues(),
        },
        onSubmit: async ({ value }) => {
            try {
                await updateServerDetails.mutateAsync(
                    updateAdminServerDetailsInput(attributes.id, {
                        ...serverDetailsBodyFromFormValues({
                            name: value.name,
                            user: Number(value.user),
                            externalId: value.externalId,
                            description: value.description,
                        }),
                        extensions: extensionFormPayload(value.extensions, server),
                    })
                );
                form.setFieldValue('extensions', initialExtensionValues());
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    // The selected owner stays in the options while a search narrows the results.
    const selectedOwnerId = useStore(form.store, (state) => state.values.user);
    const users = searchResult?.data.map(({ attributes }) => ownerOption(attributes)) ?? [];
    const persistedOwner = relationshipAttributes(attributes.relationships?.user);
    const selectedOwner = [pickedOwner, persistedOwner ? ownerOption(persistedOwner) : null].find(
        (candidate) => candidate?.id === selectedOwnerId
    );

    if (selectedOwner && !users.some((user) => user.id === selectedOwner.id)) {
        users.unshift(selectedOwner);
    }

    const pickOwner = (value: string | number) => {
        const option = users.find((user) => user.id === Number(value));

        if (option) {
            setPickedOwner(option);
        }
    };

    return (
        <Form form={form}>
            <TitledGreyBox title='Base Information'>
                <form.AppField
                    name='name'
                    validators={{
                        onChange: ({ value }) => (value.length >= 1 ? undefined : 'A server name must be provided.'),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='name'
                            label='Server Name'
                            description='Character limits: a-zA-Z0-9_- and [Space].'
                        />
                    )}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField name='externalId'>
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='external_id'
                                label='External Identifier'
                                description={
                                    'Leave empty to not assign an external identifier for this server. The external ID ' +
                                    'should be unique to this server and not be in use by any other server.'
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='user'>
                        {(field) => (
                            <field.SelectField
                                id='user'
                                label='Server Owner'
                                placeholder='Search users by name, username or email…'
                                emptyMessage={
                                    isSearching || search !== debouncedSearch
                                        ? 'Searching…'
                                        : 'No matching users found.'
                                }
                                options={users.map(ownerSelectOption)}
                                onChange={pickOwner}
                                onSearchChange={setSearch}
                                description='Changing the owner will generate a new daemon security token automatically.'
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='description'>
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='description'
                                label='Server Description'
                                description='A brief description of this server.'
                            />
                        )}
                    </form.AppField>
                </div>
                <form.AppField name='extensions'>
                    {() => (
                        <ExtensionFormFields
                            form='admin.server'
                            mode='edit'
                            resource={server}
                            error={updateServerDetails.error}
                            className='mt-6'
                        />
                    )}
                </form.AppField>
                <div className='flex justify-end mt-6'>
                    <form.AppForm>
                        <form.SubmitButton>Update Details</form.SubmitButton>
                    </form.AppForm>
                </div>
            </TitledGreyBox>
        </Form>
    );
}

export default function ServerDetailsTab() {
    const { server } = useServerDetail();

    if (server.attributes.container.installed !== 1) {
        return <ServerError message='Access to this resource is not allowed due to the current installation state.' />;
    }

    return <ServerDetailsForm key={`${server.attributes.id}:${server.attributes.updated_at}`} server={server} />;
}
