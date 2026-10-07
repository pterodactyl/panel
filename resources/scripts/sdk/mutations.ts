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

/** A panel mutation that takes the SDK's input and resolves with nothing. */
function voidMutation<TInput, TVariables>(
    mutation: Omit<SdkMutation<TVariables, unknown>, 'data'>,
    toVariables: (input: TInput) => TVariables
): SdkMutation<TInput> {
    return {
        ...mutation,
        mutateAsync: async (input) => {
            await mutation.mutateAsync(toVariables(input));
        },
        data: undefined,
    };
}

export function useCreateServerDirectory(uuid: string): SdkMutation<{ directory: string; name: string }> {
    return voidMutation(panelCreateDirectory(), ({ directory, name }) => createDirectoryInput(uuid, directory, name));
}
export function useRenameServerFiles(
    uuid: string
): SdkMutation<{ directory: string; files: { from: string; to: string }[] }> {
    return voidMutation(panelRenameFiles(), ({ directory, files }) => renameFilesInput(uuid, directory, files));
}
export function useDeleteServerFiles(uuid: string): SdkMutation<SdkFileSelection> {
    return voidMutation(panelDeleteFiles(), ({ directory, files }) => deleteFilesInput(uuid, directory, files));
}
export function useWriteServerFile(uuid: string): SdkMutation<{ file: string; content: string }> {
    return voidMutation(panelWriteFile(), ({ file, content }) => writeFileContentsInput(uuid, file, content));
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
    return voidMutation(panelSetImage(uuid), ({ image }) => ({
        path: { server_uuid: uuid },
        body: { docker_image: image },
    }));
}
export function useCreateServerBackup(
    uuid: string
): SdkMutation<{ name?: string; ignored?: string; isLocked: boolean }, SdkBackup> {
    const mutation = panelCreateBackup();
    return { ...mutation, mutateAsync: (values) => mutation.mutateAsync(createServerBackupInput(uuid, values)) };
}
/** The API requires the control permission that matches the signal. */
export function useSendServerPower(uuid: string): SdkMutation<{ signal: SdkPowerSignal }> {
    return voidMutation(panelSendPower(), ({ signal }) => sendPowerActionInput(uuid, signal));
}
/** The API requires control.console and a running server. */
export function useSendServerCommand(uuid: string): SdkMutation<{ command: string }> {
    return voidMutation(panelSendCommand(), ({ command }) => sendServerCommandInput(uuid, command));
}
