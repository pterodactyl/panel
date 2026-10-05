import type { SdkBackup, SdkServerStartup } from './server';
import type { ClientSendPowerActionRequest } from '@/api/extensionTypes';
import {
    useCreateDirectory as panelCreateDirectory,
    createDirectoryInput,
    useRenameFiles as panelRenameFiles,
    renameFilesInput,
    useDeleteFiles as panelDeleteFiles,
    deleteFilesInput,
    useSaveFileContent as panelWriteFile,
    writeFileContentsInput,
} from '@/api/server/files/queries';
import {
    useUpdateStartupVariable as panelUpdateStartup,
    useSetSelectedDockerImage as panelSetImage,
} from '@/api/server/startup/queries';
import { useCreateServerBackup as panelCreateBackup, createServerBackupInput } from '@/api/server/backups/queries';
import {
    useSendPowerAction as panelSendPower,
    sendPowerActionInput,
    useSendServerCommand as panelSendCommand,
    sendServerCommandInput,
} from '@/api/server/power/queries';

export interface SdkMutation<TInput, TOutput = void> {
    isPending: boolean;
    error: unknown;
    data: TOutput | undefined;
    mutateAsync(input: TInput): Promise<TOutput>;
    reset(): void;
}
export interface SdkFileSelection {
    directory: string;
    files: string[];
}
export type SdkPowerSignal = ClientSendPowerActionRequest['signal'];

export function useCreateServerDirectory(uuid: string): SdkMutation<{ directory: string; name: string }> {
    const mutation = panelCreateDirectory();
    return {
        ...mutation,
        mutateAsync: async ({ directory, name }) => {
            await mutation.mutateAsync(createDirectoryInput(uuid, directory, name));
        },
        data: undefined,
    };
}
export function useRenameServerFiles(
    uuid: string
): SdkMutation<{ directory: string; files: { from: string; to: string }[] }> {
    const mutation = panelRenameFiles();
    return {
        ...mutation,
        mutateAsync: async ({ directory, files }) => {
            await mutation.mutateAsync(renameFilesInput(uuid, directory, files));
        },
        data: undefined,
    };
}
export function useDeleteServerFiles(uuid: string): SdkMutation<SdkFileSelection> {
    const mutation = panelDeleteFiles();
    return {
        ...mutation,
        mutateAsync: async ({ directory, files }) => {
            await mutation.mutateAsync(deleteFilesInput(uuid, directory, files));
        },
        data: undefined,
    };
}
export function useWriteServerFile(uuid: string): SdkMutation<{ file: string; content: string }> {
    const mutation = panelWriteFile();
    return {
        ...mutation,
        mutateAsync: async ({ file, content }) => {
            await mutation.mutateAsync(writeFileContentsInput(uuid, file, content));
        },
        data: undefined,
    };
}
export function useUpdateServerStartupVariable(
    uuid: string
): SdkMutation<{ key: string; value: string }, SdkServerStartup['data'][number]> {
    const mutation = panelUpdateStartup(uuid);
    return {
        ...mutation,
        mutateAsync: ({ key, value }) => mutation.mutateAsync({ path: { server_uuid: uuid }, body: { key, value } }),
    };
}
export function useSetServerDockerImage(uuid: string): SdkMutation<{ image: string }> {
    const mutation = panelSetImage(uuid);
    return {
        ...mutation,
        mutateAsync: async ({ image }) => {
            await mutation.mutateAsync({ path: { server_uuid: uuid }, body: { docker_image: image } });
        },
        data: undefined,
    };
}
export function useCreateServerBackup(
    uuid: string
): SdkMutation<{ name?: string; ignored?: string; isLocked: boolean }, SdkBackup> {
    const mutation = panelCreateBackup();
    return { ...mutation, mutateAsync: (values) => mutation.mutateAsync(createServerBackupInput(uuid, values)) };
}
/** The API requires the control permission that matches the signal. */
export function useSendServerPower(uuid: string): SdkMutation<{ signal: SdkPowerSignal }> {
    const mutation = panelSendPower();
    return {
        ...mutation,
        mutateAsync: async ({ signal }) => {
            await mutation.mutateAsync(sendPowerActionInput(uuid, signal));
        },
        data: undefined,
    };
}
/** The API requires control.console and a running server. */
export function useSendServerCommand(uuid: string): SdkMutation<{ command: string }> {
    const mutation = panelSendCommand();
    return {
        ...mutation,
        mutateAsync: async ({ command }) => {
            await mutation.mutateAsync(sendServerCommandInput(uuid, command));
        },
        data: undefined,
    };
}
