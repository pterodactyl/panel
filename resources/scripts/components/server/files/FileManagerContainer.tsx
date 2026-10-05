import { ComponentReplacementSession } from '@/extensions/componentSession';
import { useCallback, useMemo } from 'react';
import { useCurrentServer } from '@/api/server/queries';
import { httpErrorToHuman } from '@/api/http';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { useSelectedFiles, useServerDirectory, useServerStore } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Slot from '@/extensions/Slot';
import ComponentView from '@/extensions/ComponentView';
import { useServerFiles } from '@/api/server/files/queries';
import { FileManagerExtensionContext } from './useFileManagerExtensionData';
import useFileManagerModel, { FILE_LIST_LIMIT } from './useFileManagerModel';
import { DefaultFileManager, FileManagerContext, fileManagerParts } from './FileManagerView';
import type { FileManagerSlotData } from '@/extensions/registry';

const noFiles: FileManagerSlotData['files'] = [];

function FileManagerContainerContent() {
    const server = useCurrentServer()!;
    const directory = useServerDirectory();
    const { data: files, error, refetch, isFetching } = useServerFiles(server.attributes.uuid, directory);

    const setSelectedFiles = useServerStore((state) => state.files.setSelectedFiles);
    const storedSelection = useSelectedFiles();
    const listed = useMemo(
        () => new Set(files?.data.slice(0, FILE_LIST_LIMIT).map((file) => file.attributes.name)),
        [files]
    );
    const selectedFiles = useMemo(() => {
        const present = storedSelection.filter((name) => listed.has(name));
        return present.length === storedSelection.length ? storedSelection : present;
    }, [storedSelection, listed]);
    const select = useCallback(
        (names: readonly string[]) => {
            setSelectedFiles({ directory, files: [...new Set(names)].filter((name) => listed.has(name)) });
        },
        [listed, directory, setSelectedFiles]
    );
    const refresh = useCallback(async () => {
        const result = await refetch();
        if (result.error) throw result.error;
    }, [refetch]);
    const extensionData = useMemo<FileManagerSlotData>(
        () => ({
            server,
            directory,
            files: files?.data ?? noFiles,
            selectedFiles,
            isFetching,
            setSelectedFiles: select,
            refresh,
        }),
        [server, directory, files, selectedFiles, isFetching, select, refresh]
    );
    const session = useFileManagerModel(extensionData, files !== undefined);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <FileManagerExtensionContext.Provider value={extensionData}>
            <ServerContentBlock title={'File Manager'}>
                <Slot name={'server.files.before'} />
                <FileManagerContext.Provider value={session}>
                    <ComponentView
                        name='server.files.manager'
                        resetKey={server.attributes.uuid}
                        props={{ model: session.model, Default: DefaultFileManager, parts: fileManagerParts }}
                        loading={<Spinner size={'large'} centered />}
                    />
                </FileManagerContext.Provider>
                <Slot name={'server.files.after'} />
            </ServerContentBlock>
        </FileManagerExtensionContext.Provider>
    );
}

export default function FileManagerContainer() {
    return (
        <ComponentReplacementSession>
            <FileManagerContainerContent />
        </ComponentReplacementSession>
    );
}
