import React, { useEffect } from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ServerContext } from '@/state/server';
import copyFile from '@/api/server/files/copyFile';
import { join } from 'path';
import { httpErrorToHuman } from '@/api/http';

// This component copies the given file on mount, so only mount it when
// you actually want to copy the file...
export default ({ file, onCopyComplete }: { file: FileObject; onCopyComplete: () => void }) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const getDirectoryContents = ServerContext.useStoreActions(actions => actions.files.getDirectoryContents);

    useEffect(() => {
        copyFile(uuid, join(directory, file.name))
            .then(() => getDirectoryContents(directory))
            .catch(error => {
                console.error('Error while attempting to copy file.', error);
                alert(httpErrorToHuman(error));
            });
    }, []);

    return (
        <SpinnerOverlay visible={true} large={true} fixed={true}/>
    );
};
