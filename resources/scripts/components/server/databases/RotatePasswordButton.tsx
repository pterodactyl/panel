import tw from 'twin.macro';
import React, { useState } from 'react';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import Button from '@/components/elements/Button';
import { Actions, useStoreActions } from 'easy-peasy';
import { ServerDatabase } from '@/api/server/databases/getServerDatabases';
import rotateDatabasePassword from '@/api/server/databases/rotateDatabasePassword';

export default ({ databaseId, onUpdate }: {
    databaseId: string;
    onUpdate: (database: ServerDatabase) => void;
}) => {
    const [ loading, setLoading ] = useState(false);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const server = ServerContext.useStoreState(state => state.server.data!);

    if (!databaseId) {
        return null;
    }

    const rotate = () => {
        setLoading(true);
        clearFlashes();

        rotateDatabasePassword(server.uuid, databaseId)
            .then(database => onUpdate(database))
            .catch(error => {
                console.error(error);
                addFlash({
                    type: 'error',
                    title: 'Error',
                    message: httpErrorToHuman(error),
                    key: 'database-connection-modal',
                });
            })
            .then(() => setLoading(false));
    };

    return (
        <Button isSecondary color={'primary'} css={tw`mr-2`} onClick={rotate} isLoading={loading}>
            Rotate Password
        </Button>
    );
};
