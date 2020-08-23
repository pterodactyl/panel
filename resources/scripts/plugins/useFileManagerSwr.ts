import useSWR from 'swr';
import loadDirectory, { FileObject } from '@/api/server/files/loadDirectory';
import { cleanDirectoryPath } from '@/helpers';
import useServer from '@/plugins/useServer';
import { ServerContext } from '@/state/server';

export default () => {
    const { uuid } = useServer();
    const directory = ServerContext.useStoreState(state => state.files.directory);

    return useSWR<FileObject[]>(
        `${uuid}:files:${directory}`,
        () => loadDirectory(uuid, cleanDirectoryPath(directory)),
        {
            revalidateOnMount: true,
            refreshInterval: 0,
        },
    );
};
