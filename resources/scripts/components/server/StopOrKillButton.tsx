import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { PowerAction } from '@/components/server/ServerConsole';
import Button from '@/components/elements/Button';

const StopOrKillButton = ({ onPress }: { onPress: (action: PowerAction) => void }) => {
    const [ clicked, setClicked ] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);

    useEffect(() => {
        setClicked(state => [ 'stopping' ].indexOf(status) < 0 ? false : state);
    }, [ status ]);

    return (
        <Button
            color={'red'}
            size={'xsmall'}
            disabled={status === 'offline'}
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

export default StopOrKillButton;
