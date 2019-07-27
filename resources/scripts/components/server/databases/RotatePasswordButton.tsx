import React, { useState } from 'react';
import rotateDatabasePassword from '@/api/server/rotateDatabasePassword';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { ServerContext } from '@/state/server';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';

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
        <Button className={'btn-secondary mr-2'} onClick={rotate} isLoading={loading}>
            Rotate Password
        </Button>
    );
};
