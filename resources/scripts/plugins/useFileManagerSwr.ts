import useSWR from 'swr';
import loadDirectory, { FileObject } from '@/api/server/files/loadDirectory';
import { cleanDirectoryPath } from '@/helpers';
import useServer from '@/plugins/useServer';
import { useLocation } from 'react-router';

export default () => {
    const { uuid } = useServer();
    const { hash } = useLocation();

    return useSWR<FileObject[]>(
        `${uuid}:files:${hash}`,
        () => loadDirectory(uuid, cleanDirectoryPath(hash)),
        {
            revalidateOnMount: false,
            refreshInterval: 0,
        }
    );
};
