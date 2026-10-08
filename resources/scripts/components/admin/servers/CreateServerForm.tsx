import { type ReactNode, useRef, useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import {
    createAdminServerInput,
    type EggForServer,
    useAdminEggForServer,
    useAdminServerUnassignedAllocations,
    useCreateAdminServer,
    useFetchAdminEggForServer,
} from '@/api/admin/servers/queries';
import {
    allocationLabel,
    createServerBodyFromFormValues,
    type CreateServerValues,
} from '@/components/admin/servers/helpers';
import { type NumberInputValue, requiredNumber, submittedNumber } from '@/components/admin/numberInput';
import { type AdminUser, useAllAdminUsers } from '@/api/admin/users/queries';
import { type LocationWithNodes, useAdminNodesGroupedByLocation } from '@/api/admin/nodes/queries';
import { type AdminEggListItem, useAdminEggs } from '@/api/admin/eggs/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Icon from '@/components/elements/Icon';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Label from '@/components/elements/Label';
import Spinner from '@/components/elements/Spinner';
import { Alert } from '@/components/elements/alert';
import type { AppForm } from '@/components/form';
import { useAppForm, Form } from '@/components/form';
import Select from '@/components/ui/Select';
import { relationshipData } from '@/api/relationships';

interface Values extends Omit<
    CreateServerValues,
    'databaseLimit' | 'allocationLimit' | 'backupLimit' | 'cpu' | 'memory' | 'swap' | 'disk' | 'io'
> {
    databaseLimit: NumberInputValue;
    allocationLimit: NumberInputValue;
    backupLimit: NumberInputValue;
    cpu: NumberInputValue;
    memory: NumberInputValue;
    swap: NumberInputValue;
    disk: NumberInputValue;
    io: NumberInputValue;
}

type ServerForm = AppForm<Values>;

const initialValues: Values = {
    name: '',
    description: '',
    ownerId: 0,
    startOnCompletion: true,

    allocationId: 0,
    allocationAdditional: [],

    databaseLimit: 0,
    allocationLimit: 0,
    backupLimit: 0,

    cpu: 0,
    threads: '',
    memory: 0,
    swap: 0,
    disk: 0,
    io: 500,
    enableOomKiller: true,

    eggId: 0,
    skipScripts: false,

    image: '',
    startup: '',

    environment: {},
};

const createServerValues = (values: Values): CreateServerValues => ({
    ...values,
    databaseLimit: submittedNumber(values.databaseLimit),
    allocationLimit: submittedNumber(values.allocationLimit),
    backupLimit: submittedNumber(values.backupLimit),
    cpu: submittedNumber(values.cpu),
    memory: submittedNumber(values.memory),
    swap: submittedNumber(values.swap),
    disk: submittedNumber(values.disk),
    io: submittedNumber(values.io),
});

const CoreDetailsBox = ({ form, users }: { form: ServerForm; users: AdminUser[] }) => (
    <TitledGreyBox title='Core Details'>
        <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
            <div className='space-y-6'>
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
                            description='Character limits: a-z A-Z 0-9 _ - . and [Space].'
                        />
                    )}
                </form.AppField>
                <form.AppField
                    name='ownerId'
                    validators={{
                        onChange: ({ value }) => (value >= 1 ? undefined : 'A server owner must be selected.'),
                    }}
                >
                    {(field) => (
                        <field.SelectField
                            id='ownerId'
                            label='Server Owner'
                            description='The user that this server will belong to.'
                            options={[
                                { value: 0, label: 'Select a server owner…', disabled: true },
                                ...users.map((user) => ({
                                    value: user.attributes.id ?? 0,
                                    label: `${user.attributes.email} (${user.attributes.username})`,
                                })),
                            ]}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='space-y-6'>
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
                <div className='mt-6'>
                    <form.AppField name='startOnCompletion'>
                        {(field) => (
                            <field.SwitchField
                                label='Start Server when Installed'
                                description='Start this server automatically once the installation completes.'
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
        </div>
    </TitledGreyBox>
);

const AllocationBox = ({ form, locations }: { form: ServerForm; locations: LocationWithNodes[] }) => {
    const [nodeId, setNodeId] = useState(0);

    const { data: allocationsResponse, isFetching: loading } = useAdminServerUnassignedAllocations(nodeId);
    const allocations = allocationsResponse?.data ?? [];

    const defaultAllocationId = useStore(form.store, (state) => state.values.allocationId);

    const allocationOptions = allocations.map((allocation) => ({
        value: allocation.attributes.id,
        label: allocationLabel(allocation),
    }));

    const additionalOptions = allocationOptions.filter((option) => option.value !== defaultAllocationId);

    return (
        <TitledGreyBox title='Allocation Management'>
            <div className='relative grid grid-cols-1 md:grid-cols-3 gap-6'>
                {loading && <Spinner size='small' centered />}
                <div>
                    <Label htmlFor='nodeId'>Node</Label>
                    <Select
                        id='nodeId'
                        value={nodeId}
                        placeholder='Select a node…'
                        onChange={(value) => {
                            setNodeId(Number(value));
                            form.setFieldValue('allocationId', 0);
                            form.setFieldValue('allocationAdditional', []);
                        }}
                        groups={locations.map((group) => ({
                            label: group.location.attributes.long
                                ? `${group.location.attributes.long} (${group.location.attributes.short})`
                                : group.location.attributes.short,
                            options: group.nodes.map((node) => ({ value: node.id, label: node.name })),
                        }))}
                    />
                    <p className='input-help'>The node which this server will be deployed to.</p>
                </div>
                <div>
                    <form.AppField
                        name='allocationId'
                        validators={{
                            onChange: ({ value }) =>
                                value >= 1 ? undefined : 'A default allocation must be selected.',
                        }}
                    >
                        {(field) => (
                            <field.SelectField
                                id='allocationId'
                                label='Default Allocation'
                                description='The main allocation that will be assigned to this server.'
                                disabled={nodeId <= 0}
                                options={[
                                    { value: 0, label: 'Select an allocation…', disabled: true },
                                    ...allocationOptions,
                                ]}
                                onChange={(value) =>
                                    form.setFieldValue('allocationAdditional', (current) =>
                                        current.filter((id) => id !== Number(value))
                                    )
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div>
                    <form.AppField name='allocationAdditional'>
                        {(field) => (
                            <field.MultiSelectField
                                id='allocationAdditional'
                                label='Additional Allocation(s)'
                                description='Additional allocations to assign to this server on creation.'
                                disabled={nodeId <= 0}
                                options={additionalOptions}
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
        </TitledGreyBox>
    );
};

const FeatureLimitsBox = ({ form }: { form: ServerForm }) => (
    <TitledGreyBox title='Application Feature Limits'>
        <div className='grid grid-cols-1 md:grid-cols-3 gap-6'>
            <form.AppField
                name='databaseLimit'
                validators={{ onChange: requiredNumber('A database limit must be provided.') }}
            >
                {(field) => (
                    <field.NumberField
                        id='databaseLimit'
                        label='Database Limit'
                        min={0}
                        description='The total number of databases a user is allowed to create for this server.'
                    />
                )}
            </form.AppField>
            <form.AppField
                name='allocationLimit'
                validators={{ onChange: requiredNumber('An allocation limit must be provided.') }}
            >
                {(field) => (
                    <field.NumberField
                        id='allocationLimit'
                        label='Allocation Limit'
                        min={0}
                        description='The total number of allocations a user is allowed to create for this server.'
                    />
                )}
            </form.AppField>
            <form.AppField
                name='backupLimit'
                validators={{ onChange: requiredNumber('A backup limit must be provided.') }}
            >
                {(field) => (
                    <field.NumberField
                        id='backupLimit'
                        label='Backup Limit'
                        min={0}
                        description='The total number of backups that can be created for this server.'
                    />
                )}
            </form.AppField>
        </div>
    </TitledGreyBox>
);

const ResourceManagementBox = ({ form }: { form: ServerForm }) => (
    <TitledGreyBox title='Resource Management'>
        <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
            <form.AppField name='cpu' validators={{ onChange: requiredNumber('A CPU limit must be provided.') }}>
                {(field) => (
                    <field.NumberField
                        id='cpu'
                        label='CPU Limit (%)'
                        min={0}
                        description='Set to 0 to allow unlimited CPU usage. 100% equals one thread.'
                    />
                )}
            </form.AppField>
            <form.AppField name='threads'>
                {(field) => (
                    <field.TextField
                        type='text'
                        id='threads'
                        label='CPU Pinning'
                        description='Advanced: specific CPU threads (e.g. 0, 0-1,3) or leave blank for all.'
                    />
                )}
            </form.AppField>
            <form.AppField name='memory' validators={{ onChange: requiredNumber('A memory limit must be provided.') }}>
                {(field) => (
                    <field.NumberField
                        id='memory'
                        label='Memory (MiB)'
                        min={0}
                        description='The maximum amount of memory allowed. Set to 0 for unlimited.'
                    />
                )}
            </form.AppField>
            <form.AppField name='swap' validators={{ onChange: requiredNumber('A swap limit must be provided.') }}>
                {(field) => (
                    <field.NumberField
                        id='swap'
                        label='Swap (MiB)'
                        min={-1}
                        description='Set to 0 to disable swap, or -1 to allow unlimited swap.'
                    />
                )}
            </form.AppField>
            <form.AppField name='disk' validators={{ onChange: requiredNumber('A disk limit must be provided.') }}>
                {(field) => (
                    <field.NumberField
                        id='disk'
                        label='Disk Space (MiB)'
                        min={0}
                        description='Set to 0 to allow unlimited disk usage.'
                    />
                )}
            </form.AppField>
            <form.AppField
                name='io'
                validators={{
                    onChange: requiredNumber('A block IO weight must be provided.', (value) =>
                        value >= 10 && value <= 1000 ? undefined : 'Block IO weight must be between 10 and 1000.'
                    ),
                }}
            >
                {(field) => (
                    <field.NumberField
                        id='io'
                        label='Block IO Weight'
                        min={10}
                        max={1000}
                        description='Advanced: IO performance relative to other containers (10 to 1000).'
                    />
                )}
            </form.AppField>
        </div>
        <div className='mt-6'>
            <form.AppField name='enableOomKiller'>
                {(field) => (
                    <field.SwitchField
                        label='Enable OOM Killer'
                        description={
                            'Terminates the server if it breaches its memory limits. Enabling the OOM killer may ' +
                            'cause server processes to exit unexpectedly.'
                        }
                    />
                )}
            </form.AppField>
        </div>
    </TitledGreyBox>
);

const EggConfigurationBox = ({
    form,
    eggs,
    loadEgg,
    loading,
}: {
    form: ServerForm;
    eggs: AdminEggListItem[];
    loadEgg: (eggId: number) => void;
    loading: boolean;
}) => {
    const eggId = useStore(form.store, (state) => Number(state.values.eggId ?? 0));

    return (
        <TitledGreyBox title='Egg Configuration'>
            <div className='relative space-y-6'>
                {loading && <Spinner size='small' centered />}
                <div>
                    <Label htmlFor='eggId'>Egg</Label>
                    <Select
                        id='eggId'
                        value={eggId}
                        placeholder='Select an egg…'
                        options={eggs.map((egg) => ({ value: egg.attributes.id, label: egg.attributes.name }))}
                        onChange={(value) => {
                            const nextEggId = Number(value);

                            form.setFieldValue('eggId', nextEggId);
                            loadEgg(nextEggId);
                        }}
                    />
                    <p className='input-help'>Select the egg that will define how this server should operate.</p>
                </div>
                <div>
                    <form.AppField name='skipScripts'>
                        {(field) => (
                            <field.SwitchField
                                label='Skip Egg Install Script'
                                description='Skip the egg install script during installation if one is attached.'
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
        </TitledGreyBox>
    );
};

const DockerImageBox = ({ form, egg }: { form: ServerForm; egg: EggForServer | null }) => {
    const image = useStore(form.store, (state) => state.values.image);
    const images = egg ? Object.entries(egg.attributes.docker_images) : [];
    const isCustom = !images.some(([, value]) => value === image);

    return (
        <TitledGreyBox title='Docker Configuration'>
            <div className='space-y-4'>
                <div>
                    <Label htmlFor='image'>Docker Image</Label>
                    <Select
                        id='image'
                        value={isCustom ? 'custom' : image}
                        disabled={!egg}
                        onChange={(value) => form.setFieldValue('image', value === 'custom' ? '' : String(value))}
                        options={[
                            ...images.map(([name, value]) => ({ value, label: `${name} (${value})` })),
                            { value: 'custom', label: 'Custom Image…' },
                        ]}
                    />
                </div>
                {(isCustom || images.length === 0) && (
                    <form.AppField
                        name='image'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'A Docker image must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='customImage'
                                label='Custom Image'
                                placeholder='Or enter a custom image…'
                                description='The default Docker image used to run this server.'
                            />
                        )}
                    </form.AppField>
                )}
            </div>
        </TitledGreyBox>
    );
};

const StartupBox = ({ form, egg }: { form: ServerForm; egg: EggForServer | null }) => {
    const variables = relationshipData(egg?.attributes.relationships?.variables);

    return (
        <TitledGreyBox title='Startup Configuration'>
            <form.AppField
                name='startup'
                validators={{
                    onChange: ({ value }) => (value.length >= 1 ? undefined : 'A startup command must be provided.'),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='startup'
                        label='Startup Command'
                        description='The following substitutes are available: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}.'
                    />
                )}
            </form.AppField>
            {variables.length > 0 && (
                <div className='mt-6'>
                    <p className='text-sm uppercase text-muted-foreground mb-4'>Service Variables</p>
                    <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
                        {variables.map(({ attributes }) => (
                            <form.AppField key={attributes.id} name={`environment.${attributes.env_variable}`}>
                                {(field) => (
                                    <field.TextField
                                        id={`env_${attributes.env_variable}`}
                                        label={attributes.name}
                                        description={attributes.description}
                                    />
                                )}
                            </form.AppField>
                        ))}
                    </div>
                </div>
            )}
        </TitledGreyBox>
    );
};

const EggSection = ({ form, eggs }: { form: ServerForm; eggs: AdminEggListItem[] }) => {
    const eggId = useStore(form.store, (state) => Number(state.values.eggId ?? 0));
    const fetchEgg = useFetchAdminEggForServer();

    const { data: egg = null, isFetching: loading } = useAdminEggForServer(eggId);
    const latestEggLoad = useRef(0);

    const loadEgg = async (nextEggId: number) => {
        const eggLoad = ++latestEggLoad.current;

        if (nextEggId <= 0) {
            form.setFieldValue('startup', '');
            form.setFieldValue('image', '');
            form.setFieldValue('environment', {});

            return;
        }

        const nextEgg = await fetchEgg(nextEggId);

        if (!nextEgg || eggLoad !== latestEggLoad.current) {
            return;
        }

        form.setFieldValue('startup', nextEgg.attributes.startup);

        const images = Object.values(nextEgg.attributes.docker_images);

        form.setFieldValue('image', images.length > 0 ? images[0] : '');

        const environment: Record<string, string> = {};

        for (const { attributes } of relationshipData(nextEgg.attributes.relationships?.variables)) {
            environment[attributes.env_variable] = attributes.default_value;
        }

        form.setFieldValue('environment', environment);
    };

    return (
        <>
            <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
                <EggConfigurationBox
                    form={form}
                    eggs={eggs}
                    loadEgg={(nextEggId) => void loadEgg(nextEggId)}
                    loading={loading}
                />
                <DockerImageBox form={form} egg={egg} />
            </div>
            <StartupBox form={form} egg={egg} />
        </>
    );
};

const NoNodesAlert = () => (
    <Alert type='warning' className='text-sm'>
        <span>
            You must have at least one node configured before you can add a server to this panel.{' '}
            <Link to='/panel/nodes' className='font-medium underline'>
                Manage Nodes
            </Link>
        </span>
    </Alert>
);

const CreateServerBody = ({
    loading,
    hasNodes,
    children,
}: {
    loading: boolean;
    hasNodes: boolean;
    children: ReactNode;
}) => {
    if (loading) {
        return <Spinner size='large' centered />;
    }

    if (!hasNodes) {
        return <NoNodesAlert />;
    }

    return children;
};

export default function CreateServerForm() {
    const navigate = useNavigate();
    const createServer = useCreateAdminServer();

    const { data: users = [], isLoading: usersLoading } = useAllAdminUsers();
    const { data: locations = [], isLoading: locationsLoading } = useAdminNodesGroupedByLocation();
    const { data: eggs, isLoading: eggsLoading } = useAdminEggs();

    const loading = usersLoading || locationsLoading || eggsLoading;
    const hasNodes = locations.some((location) => location.nodes.length > 0);

    const form = useAppForm({
        defaultValues: initialValues,
        onSubmit: async ({ value }) => {
            try {
                const server = await createServer.mutateAsync(
                    createAdminServerInput(createServerBodyFromFormValues(createServerValues(value)))
                );

                void navigate({ to: '/panel/servers/$id', params: { id: server.attributes.id } });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <AdminContentBlock
            title='Admin · Create Server'
            heading='Create Server'
            description='Create a game server on one of your nodes.'
        >
            <Link
                to='/panel/servers'
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                Back to Servers
            </Link>
            <CreateServerBody loading={loading} hasNodes={hasNodes}>
                <Form form={form}>
                    <div className='space-y-6'>
                        <CoreDetailsBox form={form} users={users} />
                        <AllocationBox form={form} locations={locations} />
                        <FeatureLimitsBox form={form} />
                        <ResourceManagementBox form={form} />
                        <EggSection form={form} eggs={eggs?.data ?? []} />
                        <div className='flex justify-end'>
                            <form.AppForm>
                                <form.SubmitButton>Create Server</form.SubmitButton>
                            </form.AppForm>
                        </div>
                    </div>
                </Form>
            </CreateServerBody>
        </AdminContentBlock>
    );
}
