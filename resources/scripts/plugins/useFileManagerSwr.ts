import useSWR from 'swr';
import loadDirectory, { FileObject } from '@/api/server/files/loadDirectory';
import { cleanDirectoryPath } from '@/helpers';
import { ServerContext } from '@/state/server';

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    return useSWR<FileObject[]>(
        `${uuid}:files:${directory}`,
        () => loadDirectory(uuid, cleanDirectoryPath(directory)),
        {
            focusThrottleInterval: 30000,
            revalidateOnMount: false,
            refreshInterval: 0,
            errorRetryCount: 2,
        },
    );
};
