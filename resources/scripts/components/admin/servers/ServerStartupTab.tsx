import { useRef } from 'react';
import { useStore } from '@tanstack/react-form';
import { Variable } from 'lucide-react';
import type { AppForm } from '@/components/form';
import { useAppForm, Form } from '@/components/form';
import Select from '@/components/ui/Select';
import {
    type AdminServer,
    type EggForServer,
    updateAdminServerStartupInput,
    useAdminEggForServer,
    useFetchAdminEggForServer,
    useUpdateAdminServerStartup,
} from '@/api/admin/servers/queries';
import { serverStartupBodyFromFormValues, type ServerStartupValues } from '@/components/admin/servers/helpers';
import { type AdminEggListItem, useAdminEggs } from '@/api/admin/eggs/queries';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Label from '@/components/elements/Label';
import Code from '@/components/elements/Code';
import { TextInput } from '@/components/form/controls';
import { ServerError } from '@/components/elements/ScreenBlock';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { relationshipData } from '@/api/relationships';

interface Props {
    server: AdminServer;
}

interface Values {
    startup: string;
    eggId: number;
    image: string;
    customImage: string;
    skipScripts: boolean;
    environment: Record<string, string>;
}

const serverToValues = (server: AdminServer): Values => {
    const environment: Record<string, string> = {};
    relationshipData(server.attributes.relationships?.variables).forEach(({ attributes }) => {
        environment[attributes.env_variable] = attributes.server_value ?? attributes.default_value ?? '';
    });

    return {
        startup: server.attributes.container.startup_command,
        eggId: server.attributes.egg,
        image: server.attributes.container.image,
        customImage: '',
        skipScripts: server.attributes.container.skip_scripts,
        environment,
    };
};

const EnvironmentVariables = ({ form, egg }: { form: AppForm<Values>; egg: EggForServer | null }) => {
    if (!egg) {
        return null;
    }

    const variables = relationshipData(egg.attributes.relationships?.variables);

    if (variables.length === 0) {
        return (
            <TitledGreyBox title={'Service Variables'}>
                <Empty className={emptyCompactClass}>
                    <EmptyHeader>
                        <EmptyMedia variant={'icon'}>
                            <Variable />
                        </EmptyMedia>
                        <EmptyTitle>No variables</EmptyTitle>
                        <EmptyDescription>This egg doesn&apos;t define any environment variables.</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </TitledGreyBox>
        );
    }

    return (
        <div className={'space-y-6'}>
            {variables.map(({ attributes }) => (
                <TitledGreyBox key={attributes.id} title={attributes.name}>
                    <form.AppField name={`environment.${attributes.env_variable}`}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={`environment_${attributes.env_variable}`}
                                description={attributes.description}
                            />
                        )}
                    </form.AppField>
                    <div className={'mt-3 text-xs text-muted-foreground space-y-1'}>
                        <p>
                            <strong>Startup Variable:</strong> <Code>{attributes.env_variable}</Code>
                        </p>
                        {attributes.rules && (
                            <p>
                                <strong>Input Rules:</strong> <Code className={'break-all'}>{attributes.rules}</Code>
                            </p>
                        )}
                    </div>
                </TitledGreyBox>
            ))}
        </div>
    );
};

const EggSelector = ({
    form,
    eggs,
    egg,
    loading,
    onEggChange,
}: {
    form: AppForm<Values>;
    eggs: AdminEggListItem[];
    egg: EggForServer | null;
    loading: boolean;
    onEggChange: (eggId: number) => void;
}) => {
    const eggId = useStore(form.store, (state) => state.values.eggId);
    const image = useStore(form.store, (state) => state.values.image);

    const dockerImages = egg?.attributes.docker_images ?? {};
    const hasPresetImages = Object.keys(dockerImages).length > 0;
    const imageOptions = egg
        ? [
              ...Object.entries(dockerImages).map(([label, value]) => ({
                  value,
                  label: `${label} (${value})`,
              })),
              ...(hasPresetImages && !Object.values(dockerImages).includes(image)
                  ? [{ value: image, label: `${image} (custom)` }]
                  : []),
          ]
        : [];

    return (
        <>
            <TitledGreyBox title={'Service Configuration'}>
                <p className={'text-xs text-destructive mb-4'}>
                    Changing any of the values below will result in the server being reinstalled. The server will be
                    stopped and will then proceed.
                </p>
                <div>
                    <Label htmlFor={'eggId'}>Egg</Label>
                    <Select
                        id={'eggId'}
                        value={eggId}
                        disabled={loading || eggs.length === 0}
                        options={eggs.map((candidate) => ({
                            value: candidate.attributes.id,
                            label: candidate.attributes.name,
                        }))}
                        onChange={(value) => {
                            const nextEggId = Number(value);
                            form.setFieldValue('eggId', nextEggId);
                            onEggChange(nextEggId);
                        }}
                    />
                </div>
                <div className={'mt-6'}>
                    <form.AppField name={'skipScripts'}>
                        {(field) => (
                            <field.SwitchField
                                label={'Skip Egg Install Script'}
                                description={'Skip the egg install script when reinstalling the server.'}
                            />
                        )}
                    </form.AppField>
                </div>
            </TitledGreyBox>
            <TitledGreyBox title={'Docker Image Configuration'} className={'mt-6'}>
                {hasPresetImages ? (
                    <>
                        <Label htmlFor={'image'}>Image</Label>
                        <Select
                            id={'image'}
                            value={image}
                            options={imageOptions}
                            onChange={(value) => {
                                form.setFieldValue('image', String(value));
                                form.setFieldValue('customImage', '');
                            }}
                        />
                        <div className={'mt-6'}>
                            <form.AppField name={'customImage'}>
                                {(field) => (
                                    <field.TextField
                                        type={'text'}
                                        id={'custom_docker_image'}
                                        label={'Custom Docker Image'}
                                        placeholder={'Or enter a custom image...'}
                                    />
                                )}
                            </form.AppField>
                        </div>
                    </>
                ) : (
                    <form.AppField name={'image'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'image'}
                                label={'Image'}
                                placeholder={'Enter a docker image...'}
                            />
                        )}
                    </form.AppField>
                )}
                <p className={'mt-1 text-xs text-muted-foreground'}>
                    Select an image from the dropdown or enter a custom Docker image.
                </p>
            </TitledGreyBox>
        </>
    );
};

function ServerStartupTabContent({ server }: Props) {
    const fetchEggForServer = useFetchAdminEggForServer();
    const updateServerStartup = useUpdateAdminServerStartup();

    const { data: eggs } = useAdminEggs();

    const form = useAppForm({
        defaultValues: serverToValues(server),
        onSubmit: async ({ value }) => {
            const payload: ServerStartupValues = {
                startup: value.startup,
                eggId: Number(value.eggId),
                image: value.customImage.trim() || value.image,
                skipScripts: value.skipScripts,
                environment: value.environment,
            };

            try {
                await updateServerStartup.mutateAsync(
                    updateAdminServerStartupInput(server.attributes.id, serverStartupBodyFromFormValues(payload))
                );
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const eggId = useStore(form.store, (state) => Number(state.values.eggId));
    const { data: egg = null, isFetching: loadingEgg } = useAdminEggForServer(eggId);
    const latestEggLoad = useRef(0);

    const loadEgg = async (nextEggId: number) => {
        const eggLoad = ++latestEggLoad.current;

        if (nextEggId <= 0) {
            form.setFieldValue('environment', {});
            form.setFieldValue('image', '');
            form.setFieldValue('customImage', '');
            return;
        }

        const nextEgg = await fetchEggForServer(nextEggId);
        if (eggLoad !== latestEggLoad.current) {
            return;
        }

        if (!nextEgg) {
            form.setFieldValue('environment', {});
            form.setFieldValue('image', '');
            form.setFieldValue('customImage', '');
            return;
        }

        const isCurrentEgg = nextEggId === server.attributes.egg;
        const current = relationshipData(server.attributes.relationships?.variables);
        const environment: Record<string, string> = {};
        relationshipData(nextEgg.attributes.relationships?.variables).forEach(({ attributes }) => {
            const existing = current.find((variable) => variable.attributes.env_variable === attributes.env_variable);
            environment[attributes.env_variable] =
                isCurrentEgg && existing
                    ? (existing.attributes.server_value ?? existing.attributes.default_value ?? '')
                    : attributes.default_value;
        });
        form.setFieldValue('environment', environment);

        const images = Object.values(nextEgg.attributes.docker_images);
        if (isCurrentEgg) {
            form.setFieldValue('image', server.attributes.container.image);
        } else if (images.length > 0) {
            form.setFieldValue('image', images[0]);
        }
        form.setFieldValue('customImage', '');
    };

    return (
        <Form form={form}>
            <TitledGreyBox title={'Startup Command Modification'}>
                <form.AppField name={'startup'}>
                    {(field) => (
                        <field.TextField
                            type={'text'}
                            id={'startup'}
                            label={'Startup Command'}
                            description={
                                'The following variables are available by default: {{SERVER_MEMORY}}, ' +
                                '{{SERVER_IP}}, and {{SERVER_PORT}}.'
                            }
                        />
                    )}
                </form.AppField>
                <div className={'mt-6'}>
                    <Label htmlFor={'default_startup'}>Default Service Start Command</Label>
                    <TextInput
                        id={'default_startup'}
                        type={'text'}
                        readOnly
                        value={egg?.attributes.startup || 'Startup not defined'}
                    />
                </div>
            </TitledGreyBox>
            <div className={'grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6'}>
                <div>
                    <EggSelector
                        form={form}
                        eggs={eggs?.data ?? []}
                        egg={egg}
                        loading={loadingEgg}
                        onEggChange={(nextEggId) => void loadEgg(nextEggId)}
                    />
                </div>
                <EnvironmentVariables form={form} egg={egg} />
            </div>
            <div className={'flex justify-end mt-6'}>
                <form.AppForm>
                    <form.SubmitButton>Save Modifications</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

export default function ServerStartupTab() {
    const { server } = useServerDetail();

    if (server.attributes.container.installed !== 1) {
        return (
            <ServerError message={'Access to this resource is not allowed due to the current installation state.'} />
        );
    }

    return <ServerStartupTabContent key={server.attributes.id} server={server} />;
}
