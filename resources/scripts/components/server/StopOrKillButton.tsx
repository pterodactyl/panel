import isEqual from 'react-fast-compare';
import { ServerContext } from '@/state/server';
import Button from '@/components/elements/Button';
import React, { memo, useEffect, useState } from 'react';
import { PowerAction } from '@/components/server/ServerConsole';

const StopOrKillButton = ({ onPress }: { onPress: (action: PowerAction) => void }) => {
    const [ clicked, setClicked ] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);

    useEffect(() => {
        setClicked(status === 'stopping');
    }, [ status ]);

    return (
        <Button
            color={'red'}
            size={'xsmall'}
            disabled={!status || status === 'offline'}
            onClick={e => {
                e.preventDefault();
                onPress(clicked ? 'kill' : 'stop');
                setClicked(true);
            }}
        >
            {clicked ? 'Kill' : 'Stop'}
        </Button>
    );
};

export default memo(StopOrKillButton, isEqual);
