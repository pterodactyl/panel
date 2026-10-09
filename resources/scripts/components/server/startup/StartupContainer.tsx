import { Variable } from 'lucide-react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import VariableBox from '@/components/server/startup/VariableBox';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import Select from '@/components/ui/Select';
import { TextInput } from '@/components/form/controls';
import InputSpinner from '@/components/elements/InputSpinner';
import { useCurrentServer } from '@/api/server/queries';
import { useServerStartup, useSetSelectedDockerImage } from '@/api/server/startup/queries';
import Slot from '@/extensions/Slot';
import { usePermissions } from '@/plugins/usePermissions';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { cn } from '@/lib/cn';

const StartupContainer = () => {
    const server = useCurrentServer()!;
    const uuid = server.attributes.uuid;

    const { data, error, isFetching, refetch } = useServerStartup(uuid);

    const updateDockerImage = useSetSelectedDockerImage(uuid);
    const [canChangeDockerImage] = usePermissions(['startup.docker-image']);
    const dockerImages = data?.meta?.docker_images ?? {};
    const isCustomImage =
        data &&
        !Object.values(dockerImages)
            .map((v) => v.toLowerCase())
            .includes(server.attributes.docker_image.toLowerCase());

    const updateSelectedDockerImage = (image: string) => {
        updateDockerImage.mutate({ path: { server_uuid: uuid }, body: { docker_image: image } });
    };

    if (!data) {
        if (error && !isFetching) {
            return <ServerError title='Oops!' message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
        }

        return <Spinner centered size={Spinner.Size.LARGE} />;
    }

    return (
        <ServerContentBlock title='Startup Settings'>
            <Slot
                name='server.startup.form'
                data={{
                    server,
                    configuration: data,
                    isPending: updateDockerImage.isPending,
                    canChangeDockerImage: canChangeDockerImage && !isCustomImage,
                    setDockerImage: async (image) => {
                        if (!canChangeDockerImage || isCustomImage || !Object.values(dockerImages).includes(image)) {
                            throw new Error('This Docker image cannot be selected for the current server.');
                        }

                        await updateDockerImage.mutateAsync({
                            path: { server_uuid: uuid },
                            body: { docker_image: image },
                        });
                    },
                    refresh: async () => {
                        const result = await refetch();

                        if (result.error) {
                            throw result.error;
                        }
                    },
                }}
            />
            <div className='md:flex md:items-start'>
                <TitledGreyBox title='Startup Command' className='flex-1'>
                    <div className='px-1 py-2'>
                        <p className='rounded-sm bg-terminal px-4 py-2 font-mono'>{data.meta?.startup_command}</p>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title='Docker Image' className='flex-1 lg:flex-none lg:w-1/3 mt-8 md:mt-0 md:ml-10'>
                    {Object.keys(dockerImages).length > 1 && !isCustomImage ? (
                        <>
                            <InputSpinner visible={updateDockerImage.isPending}>
                                <Select
                                    disabled={Object.keys(dockerImages).length < 2}
                                    value={server.attributes.docker_image}
                                    onChange={(value) => updateSelectedDockerImage(String(value))}
                                    options={Object.keys(dockerImages).map((key) => ({
                                        value: dockerImages[key],
                                        label: key,
                                    }))}
                                />
                            </InputSpinner>
                            <p className='text-xs text-muted-foreground mt-2'>
                                This is an advanced feature allowing you to select a Docker image to use when running
                                this server instance.
                            </p>
                        </>
                    ) : (
                        <>
                            <TextInput disabled readOnly value={server.attributes.docker_image} />
                            {isCustomImage && (
                                <p className='text-xs text-muted-foreground mt-2'>
                                    This server's Docker image has been manually set by an administrator and cannot be
                                    changed through this UI.
                                </p>
                            )}
                        </>
                    )}
                </TitledGreyBox>
            </div>
            <h3 className='mt-8 mb-2 text-2xl'>Variables</h3>
            {data.data.length === 0 ? (
                <Empty className={cn(emptyCompactClass, 'border')}>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <Variable />
                        </EmptyMedia>
                        <EmptyTitle>No variables</EmptyTitle>
                        <EmptyDescription>
                            This server doesn&apos;t have any startup variables to configure.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <div className='grid gap-8 md:grid-cols-2'>
                    {data.data.map((variable) => (
                        <VariableBox key={variable.attributes.env_variable} variable={variable} />
                    ))}
                </div>
            )}
        </ServerContentBlock>
    );
};

export default StartupContainer;
