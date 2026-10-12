import { useMemo } from 'react';
import { httpErrorToHuman } from '@/api/http';
import { useCurrentServer } from '@/api/server/queries';
import {
    useDockerImagePending,
    useServerStartup,
    useSetSelectedDockerImage,
    useUpdateStartupVariable,
    type ServerStartupVariable,
} from '@/api/server/startup/queries';
import type { StartupFormSlotData } from '@/extensions/registry';
import { usePermissions } from '@/plugins/usePermissions';
import { createTestServer } from '../fixtures';
import { viewPage } from '../pages';
import { defineView } from '../registry';
import type { ServerPageModel } from './page';

export interface StartupPageModel extends ServerPageModel {
    /** The startup configuration has not loaded yet. */
    readonly loading: boolean;
    /** What the `server.startup.form` slot receives; null until the configuration has loaded. */
    readonly slot: StartupFormSlotData | null;
}
export interface StartupCommandModel {
    /** The command with the server's variables filled in. */
    readonly command: string | null;
}
export interface StartupImageModel {
    readonly image: string;
    /** The egg's images by label. */
    readonly images: readonly { readonly label: string; readonly value: string }[];
    /** An administrator set an image the egg does not offer, so it cannot be changed here. */
    readonly custom: boolean;
    /** The user may switch images; the server refuses a switch without it. */
    readonly allowed: boolean;
    readonly pending: boolean;
    /** Core has already shown a toast for a rejection. */
    readonly select: (image: string) => Promise<void>;
}
/** How a variable is edited, read from its validation rules. */
export type StartupVariableControl =
    | { readonly kind: 'switch'; readonly on: string; readonly off: string }
    | { readonly kind: 'select'; readonly options: readonly string[] }
    | { readonly kind: 'text' };
export interface StartupVariable {
    /** The environment variable, which identifies it. */
    readonly key: string;
    readonly name: string;
    readonly description: string;
    /** The server's value, or the default when it has none. */
    readonly value: string;
    readonly defaultValue: string;
    /** The egg lets users change it. */
    readonly editable: boolean;
    readonly control: StartupVariableControl;
}
export interface StartupVariablesModel {
    readonly variables: readonly StartupVariable[];
    /** The user may change editable variables. */
    readonly canEdit: boolean;
    /** Saves a value; resolves once saved. Core has already shown a toast for a rejection. */
    readonly update: (key: string, value: string) => Promise<void>;
}

declare module '../registry' {
    interface ViewModels {
        'page.server.startup': StartupPageModel;
        'server.startup.command': StartupCommandModel;
        'server.startup.image': StartupImageModel;
        'server.startup.variables': StartupVariablesModel;
    }
}

const booleanRules = new Set(['boolean', 'in:0,1', 'in:1,0', 'in:true,false', 'in:false,true']);

function variableControl(rules: string): StartupVariableControl {
    const parsed = rules.split('|');

    if (parsed.some((rule) => booleanRules.has(rule))) {
        return parsed.includes('string')
            ? { kind: 'switch', on: 'true', off: 'false' }
            : { kind: 'switch', on: '1', off: '0' };
    }

    const options = parsed.find((rule) => rule.startsWith('in:'))?.split(',') ?? [];

    return options.length
        ? { kind: 'select', options: options.map((option) => option.replace('in:', '')) }
        : { kind: 'text' };
}

const startupVariable = ({ attributes }: ServerStartupVariable): StartupVariable => ({
    key: attributes.env_variable,
    name: attributes.name,
    description: attributes.description,
    value: attributes.server_value ?? attributes.default_value,
    defaultValue: attributes.default_value,
    editable: attributes.is_editable,
    control: variableControl(attributes.rules),
});

function useStartup() {
    const server = useCurrentServer();
    const uuid = server?.attributes.uuid ?? '';

    return { server, uuid, query: useServerStartup(uuid) };
}

/** The image the server runs, the egg's images, and switching between them. */
function useDockerImage() {
    const { server, uuid, query } = useStartup();
    const { mutateAsync } = useSetSelectedDockerImage(uuid);
    const [allowed] = usePermissions(['startup.docker-image']);
    const pending = useDockerImagePending(uuid);
    const image = server?.attributes.docker_image ?? '';
    const offered = query.data?.meta?.docker_images;

    return useMemo(() => {
        const images = Object.entries(offered ?? {}).map(([label, value]) => ({ label, value }));
        const custom =
            query.data !== undefined && !images.some(({ value }) => value.toLowerCase() === image.toLowerCase());

        return {
            image,
            images,
            custom,
            allowed,
            pending,
            select: async (selected: string) => {
                await mutateAsync({ path: { server_uuid: uuid }, body: { docker_image: selected } });
            },
        };
    }, [image, offered, query.data, allowed, pending, mutateAsync, uuid]);
}

function useStartupPageModel(): StartupPageModel {
    const { server, query } = useStartup();
    const docker = useDockerImage();
    const { data, error, isFetching, refetch } = query;

    return useMemo(() => {
        const failed = !data && error && !isFetching;

        return {
            loading: !data && !failed,
            error: failed ? httpErrorToHuman(error) : null,
            retry: () => void refetch(),
            slot:
                data && server
                    ? {
                          server,
                          configuration: data,
                          isPending: docker.pending,
                          canChangeDockerImage: docker.allowed && !docker.custom,
                          setDockerImage: async (image) => {
                              if (
                                  !docker.allowed ||
                                  docker.custom ||
                                  !docker.images.some(({ value }) => value === image)
                              ) {
                                  throw new Error('This Docker image cannot be selected for the current server.');
                              }

                              await docker.select(image);
                          },
                          refresh: async () => {
                              const result = await refetch();

                              if (result.error) {
                                  throw result.error;
                              }
                          },
                      }
                    : null,
        };
    }, [server, data, error, isFetching, refetch, docker]);
}

function useStartupCommandModel(): StartupCommandModel {
    const command = useStartup().query.data?.meta?.startup_command ?? null;

    return useMemo(() => ({ command }), [command]);
}

function useStartupVariablesModel(): StartupVariablesModel {
    const { uuid, query } = useStartup();
    const [canEdit] = usePermissions(['startup.update']);
    const { mutateAsync } = useUpdateStartupVariable(uuid);
    const listed = query.data?.data;

    return useMemo(
        () => ({
            variables: (listed ?? []).map(startupVariable),
            canEdit,
            update: async (key, value) => {
                await mutateAsync({ path: { server_uuid: uuid }, body: { key, value } });
            },
        }),
        [listed, canEdit, mutateAsync, uuid]
    );
}

const resolved = async () => {};

const images = [
    { label: 'Java 21', value: 'ghcr.io/pterodactyl/yolks:java_21' },
    { label: 'Java 17', value: 'ghcr.io/pterodactyl/yolks:java_17' },
];
const image = (options: Partial<StartupImageModel> = {}): StartupImageModel => ({
    image: images[0].value,
    images,
    custom: false,
    allowed: true,
    pending: false,
    select: resolved,
    ...options,
});
const variable = (options: Partial<StartupVariable> & Pick<StartupVariable, 'key' | 'name'>): StartupVariable => ({
    description: '',
    value: '',
    defaultValue: '',
    editable: true,
    control: { kind: 'text' },
    ...options,
});
const variables = [
    variable({
        key: 'SERVER_JARFILE',
        name: 'Server Jar File',
        description: 'The name of the server jarfile to run the server with.',
        value: 'server.jar',
        defaultValue: 'server.jar',
    }),
    variable({
        key: 'MINECRAFT_VERSION',
        name: 'Minecraft Version',
        description: 'The version of Minecraft to download.',
        value: 'latest',
        defaultValue: 'latest',
        control: { kind: 'select', options: ['latest', 'snapshot', '1.21.4'] },
    }),
    variable({
        key: 'AUTO_UPDATE',
        name: 'Auto Update',
        description: 'Update the server on every start.',
        value: '1',
        defaultValue: '0',
        control: { kind: 'switch', on: '1', off: '0' },
    }),
    variable({
        key: 'BUILD_NUMBER',
        name: 'Build Number',
        description: 'Set by the administrator.',
        value: '412',
        defaultValue: 'latest',
        editable: false,
    }),
];
const configuration: StartupFormSlotData['configuration'] = {
    object: 'list',
    data: [],
    meta: {
        startup_command: 'java -Xms128M -Xmx2048M -jar server.jar nogui',
        raw_startup_command: 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}} nogui',
        docker_images: Object.fromEntries(images.map(({ label, value }) => [label, value])),
    },
};

export const startupViews = [
    defineView('page.server.startup', {
        ...viewPage('page.server.startup'),
        use: useStartupPageModel,
        slots: ['server.startup.before', 'server.startup.after', 'server.startup.form'],
        fixtures: {
            default: () => ({
                loading: false,
                error: null,
                retry: () => {},
                slot: {
                    server: createTestServer(),
                    configuration,
                    isPending: false,
                    canChangeDockerImage: true,
                    setDockerImage: resolved,
                    refresh: resolved,
                },
            }),
            loading: () => ({ loading: true, error: null, retry: () => {}, slot: null }),
            error: () => ({
                loading: false,
                error: 'Request failed with status code 500.',
                retry: () => {},
                slot: null,
            }),
        },
    }),
    defineView('server.startup.command', {
        context: 'server',
        use: useStartupCommandModel,
        fixtures: { default: () => ({ command: 'java -Xms128M -Xmx2048M -jar server.jar nogui' }) },
    }),
    defineView('server.startup.image', {
        context: 'server',
        use: useDockerImage,
        fixtures: {
            selectable: () => image(),
            single: () => image({ images: images.slice(0, 1) }),
            custom: () => image({ image: 'ghcr.io/example/custom:latest', custom: true }),
            pending: () => image({ pending: true }),
        },
    }),
    defineView('server.startup.variables', {
        context: 'server',
        use: useStartupVariablesModel,
        fixtures: {
            variables: () => ({ variables, canEdit: true, update: resolved }),
            readOnly: () => ({ variables, canEdit: false, update: resolved }),
            empty: () => ({ variables: [], canEdit: true, update: resolved }),
        },
    }),
];
