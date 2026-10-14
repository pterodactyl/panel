import { useMemo, useState } from 'react';
import { useCurrentUser } from '@/api/account/queries';
import { useCurrentServerUuid } from '@/api/server/queries';
import { useSaveFileContent, writeFileContentsInput } from '@/api/server/files/queries';
import { useServerStartup, useSetSelectedDockerImage, useUpdateStartupVariable } from '@/api/server/startup/queries';
import { useDialogState } from '@/components/elements/dialog';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import { extractHytaleOauthUrl, hytaleOauthUrlPattern } from '@/components/server/features/hytaleOauth';
import { useConsolePatternTrigger } from '@/components/server/features/useConsolePatternTrigger';
import { usePermissions } from '@/plugins/usePermissions';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { useServerStatus, useSocketInstance } from '@/state/server';
import type {
    EulaPromptModel,
    GslTokenPromptModel,
    HytaleOauthPromptModel,
    JavaVersionPromptModel,
    LimitPromptModel,
} from '@/views/server/features';

const eulaErrors = ['you need to agree to the eula in order to run the server'];
const gslTokenErrors = ['(gsl token expired)', '(account not found)'];
const steamDiskSpaceErrors = ['steamcmd needs 250mb of free disk space to update', '0x202 after update job'];
const pidLimitErrors = [
    'pthread_create failed',
    'failed to create thread',
    'unable to create thread',
    'unable to create native thread',
    'unable to create new native thread',
    'exception in thread "craft async scheduler management thread"',
];
const javaVersionErrors = [
    'minecraft 1.17 requires running the server with java 16 or above',
    'minecraft 1.18 requires running the server with java 17 or above',
    'java.lang.unsupportedclassversionerror',
    'unsupported major.minor version',
    'has been compiled by a more recent version of the java runtime',
];
const hytaleOauthUrlPatterns = [hytaleOauthUrlPattern];

export function useEulaPrompt(): EulaPromptModel {
    const dialog = useConsolePatternTrigger(eulaErrors);
    const uuid = useCurrentServerUuid()!;
    const status = useServerStatus();
    const instance = useSocketInstance();
    const { mutateAsync, isPending } = useSaveFileContent();
    const { open, hide } = dialog;

    return useMemo(
        () => ({
            open,
            close: hide,
            pending: isPending,
            accept: async () => {
                await mutateAsync(writeFileContentsInput(uuid, 'eula.txt', 'eula=true'));
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                hide();
            },
        }),
        [open, hide, isPending, mutateAsync, uuid, status, instance]
    );
}

export function useGslTokenPrompt(): GslTokenPromptModel {
    const { open, hide } = useConsolePatternTrigger(gslTokenErrors);
    const uuid = useCurrentServerUuid()!;
    const instance = useSocketInstance();
    const { mutateAsync, isPending } = useUpdateStartupVariable(uuid);

    return useMemo(
        () => ({
            open,
            close: hide,
            pending: isPending,
            submit: async (token: string) => {
                await mutateAsync({ path: { server_uuid: uuid }, body: { key: 'STEAM_ACC', value: token } });
                instance?.send(SocketRequest.SET_STATE, 'restart');
                hide();
            },
        }),
        [open, hide, isPending, mutateAsync, uuid, instance]
    );
}

function useLimitPrompt(patterns: readonly string[]): LimitPromptModel {
    const { open, hide } = useConsolePatternTrigger(patterns);
    const { rootAdmin } = useCurrentUser();

    return useMemo(() => ({ open, close: hide, admin: rootAdmin }), [open, hide, rootAdmin]);
}

export const usePidLimitPrompt = (): LimitPromptModel => useLimitPrompt(pidLimitErrors);
export const useSteamDiskSpacePrompt = (): LimitPromptModel => useLimitPrompt(steamDiskSpaceErrors);

export function useHytaleOauthPrompt(): HytaleOauthPromptModel {
    const [link, setLink] = useState<string | null>(null);
    const { open, hide } = useConsolePatternTrigger(hytaleOauthUrlPatterns, {
        onTrigger: (line) => setLink(extractHytaleOauthUrl(line)),
    });

    return useMemo(() => {
        const close = () => {
            hide();
            setLink(null);
        };

        return {
            open,
            close,
            link,
            login: () => {
                if (link) {
                    window.open(link, '_blank', 'noopener,noreferrer');
                    close();
                }
            },
        };
    }, [open, hide, link]);
}

export function useJavaVersionPrompt(): JavaVersionPromptModel {
    const dialog = useDialogState();
    const [override, setOverride] = useState('');
    const uuid = useCurrentServerUuid()!;
    const status = useServerStatus();
    const instance = useSocketInstance();
    const [canChangeImage] = usePermissions(['startup.docker-image']);
    const { mutateAsync, isPending } = useSetSelectedDockerImage(uuid);
    const { data, isFetching } = useServerStartup(uuid, { enabled: dialog.open });
    const { open, show, hide } = dialog;

    useWebsocketEvent(SocketEvent.CONSOLE_OUTPUT, (line) => {
        if (status === 'running') {
            return;
        }

        if (javaVersionErrors.some((pattern) => line.toLowerCase().includes(pattern))) {
            setOverride('');
            show();
        }
    });

    return useMemo(() => {
        const images = data
            ? Object.entries(data.meta?.docker_images ?? {}).map(([label, value]) => ({ label, value }))
            : null;
        const selected = override || images?.[0]?.value || '';

        return {
            open,
            close: hide,
            canChangeImage,
            images,
            loading: !data || isFetching,
            selected,
            select: setOverride,
            pending: isPending,
            update: async () => {
                await mutateAsync({ path: { server_uuid: uuid }, body: { docker_image: selected } });
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                hide();
            },
        };
    }, [open, hide, canChangeImage, data, isFetching, override, isPending, mutateAsync, uuid, status, instance]);
}
