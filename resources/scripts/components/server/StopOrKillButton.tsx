import React, { memo, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { PowerAction } from '@/components/server/ServerConsole';
import Button from '@/components/elements/Button';
import isEqual from 'react-fast-compare';
import { useTranslation } from 'react-i18next';

const StopOrKillButton = ({ onPress }: { onPress: (action: PowerAction) => void }) => {
    const [ clicked, setClicked ] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);
    const { t } = useTranslation('server');

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
            {clicked ? t('kill') : t('stop')}
        </Button>
    );
};

export default memo(StopOrKillButton, isEqual);
