import { useMemo } from 'react';
import { useCurrentServer } from '@/api/server/queries';
import {
    useEulaPrompt,
    useGslTokenPrompt,
    useHytaleOauthPrompt,
    useJavaVersionPrompt,
    usePidLimitPrompt,
    useSteamDiskSpacePrompt,
} from '@/components/server/features/prompts';
import { defineView } from '../registry';

/** The egg features whose prompt the console opens when the server's output asks for it. */
export const SERVER_FEATURES = {
    eula: 'server.feature.eula',
    java_version: 'server.feature.javaVersion',
    gsl_token: 'server.feature.gslToken',
    pid_limit: 'server.feature.pidLimit',
    steam_disk_space: 'server.feature.steamDiskSpace',
    hytale_oauth: 'server.feature.hytaleOauth',
} as const;
export type ServerFeatureView = (typeof SERVER_FEATURES)[keyof typeof SERVER_FEATURES];

export interface ServerFeaturesModel {
    /** The prompt views of the features the server's egg enables. */
    readonly views: readonly ServerFeatureView[];
}

/** A dialog the console opens itself when the server's output matches the feature's errors. */
export interface ServerPromptModel {
    readonly open: boolean;
    readonly close: () => void;
}
export interface EulaPromptModel extends ServerPromptModel {
    readonly pending: boolean;
    /** Writes `eula=true` to eula.txt, restarts an offline server and closes the prompt. */
    readonly accept: () => Promise<void>;
}
export interface GslTokenPromptModel extends ServerPromptModel {
    readonly pending: boolean;
    /** Saves the token (an empty one removes it), restarts the server and closes the prompt. */
    readonly submit: (token: string) => Promise<void>;
}
export interface JavaVersionPromptModel extends ServerPromptModel {
    /** The user may change the Docker image; otherwise the prompt only explains. */
    readonly canChangeImage: boolean;
    /** The egg's images; null until they have loaded. */
    readonly images: readonly { readonly label: string; readonly value: string }[] | null;
    readonly loading: boolean;
    readonly selected: string;
    readonly select: (image: string) => void;
    readonly pending: boolean;
    /** Switches to the selected image, restarts an offline server and closes the prompt. */
    readonly update: () => Promise<void>;
}
/** A resource limit was hit; admins get the fix, other users are told to contact one. */
export interface LimitPromptModel extends ServerPromptModel {
    readonly admin: boolean;
}
export interface HytaleOauthPromptModel extends ServerPromptModel {
    /** The verification link the server printed. */
    readonly link: string | null;
    /** Opens the link in a new tab and closes the prompt. */
    readonly login: () => void;
}

declare module '../registry' {
    interface ViewModels {
        'server.features': ServerFeaturesModel;
        'server.feature.eula': EulaPromptModel;
        'server.feature.javaVersion': JavaVersionPromptModel;
        'server.feature.gslToken': GslTokenPromptModel;
        'server.feature.pidLimit': LimitPromptModel;
        'server.feature.steamDiskSpace': LimitPromptModel;
        'server.feature.hytaleOauth': HytaleOauthPromptModel;
    }
}

function useServerFeaturesModel(): ServerFeaturesModel {
    const features = useCurrentServer((server) => server.attributes.egg_features);

    return useMemo(() => {
        const enabled = new Set((features ?? []).map((feature) => feature.toLowerCase()));

        return {
            views: Object.entries(SERVER_FEATURES).flatMap(([feature, view]) => (enabled.has(feature) ? [view] : [])),
        };
    }, [features]);
}

const noop = () => {};

const resolved = async () => {};

const closed = { open: false, close: noop };
const opened = { open: true, close: noop };
const images = [
    { label: 'Java 21', value: 'ghcr.io/pterodactyl/yolks:java_21' },
    { label: 'Java 17', value: 'ghcr.io/pterodactyl/yolks:java_17' },
];
const javaVersion = (open: boolean, canChangeImage = true): JavaVersionPromptModel => ({
    open,
    close: noop,
    canChangeImage,
    images,
    loading: false,
    selected: images[0].value,
    select: noop,
    pending: false,
    update: resolved,
});

export const featureViews = [
    defineView('server.features', {
        context: 'server',
        use: useServerFeaturesModel,
        fixtures: {
            minecraft: () => ({
                views: [SERVER_FEATURES.eula, SERVER_FEATURES.java_version, SERVER_FEATURES.pid_limit],
            }),
            none: () => ({ views: [] }),
        },
    }),
    defineView('server.feature.eula', {
        context: 'server',
        use: useEulaPrompt,
        fixtures: {
            closed: () => ({ ...closed, pending: false, accept: resolved }),
            open: () => ({ ...opened, pending: false, accept: resolved }),
            accepting: () => ({ ...opened, pending: true, accept: resolved }),
        },
    }),
    defineView('server.feature.javaVersion', {
        context: 'server',
        use: useJavaVersionPrompt,
        fixtures: {
            closed: () => javaVersion(false),
            open: () => javaVersion(true),
            loading: () => ({ ...javaVersion(true), images: null, loading: true }),
            noPermission: () => javaVersion(true, false),
        },
    }),
    defineView('server.feature.gslToken', {
        context: 'server',
        use: useGslTokenPrompt,
        fixtures: {
            closed: () => ({ ...closed, pending: false, submit: resolved }),
            open: () => ({ ...opened, pending: false, submit: resolved }),
        },
    }),
    defineView('server.feature.pidLimit', {
        context: 'server',
        use: usePidLimitPrompt,
        fixtures: {
            closed: () => ({ ...closed, admin: false }),
            admin: () => ({ ...opened, admin: true }),
            user: () => ({ ...opened, admin: false }),
        },
    }),
    defineView('server.feature.steamDiskSpace', {
        context: 'server',
        use: useSteamDiskSpacePrompt,
        fixtures: {
            closed: () => ({ ...closed, admin: false }),
            admin: () => ({ ...opened, admin: true }),
            user: () => ({ ...opened, admin: false }),
        },
    }),
    defineView('server.feature.hytaleOauth', {
        context: 'server',
        use: useHytaleOauthPrompt,
        fixtures: {
            closed: () => ({ ...closed, link: null, login: noop }),
            open: () => ({
                ...opened,
                link: 'https://oauth.accounts.hytale.com/oauth2/device/verify?user_code=ABCD-1234',
                login: noop,
            }),
        },
    }),
];
