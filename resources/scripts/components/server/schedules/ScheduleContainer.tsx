import React, { useEffect, useState } from 'react';
import getServerSchedules from '@/api/server/schedules/getServerSchedules';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { useHistory, useRouteMatch } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import { httpErrorToHuman } from '@/api/http';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';
import Can from '@/components/elements/Can';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Button from '@/components/elements/Button';
import ServerContentBlock from '@/components/elements/ServerContentBlock';

export default () => {
    const match = useRouteMatch();
    const history = useHistory();

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, addError } = useFlash();
    const [ loading, setLoading ] = useState(true);
    const [ visible, setVisible ] = useState(false);

    const schedules = ServerContext.useStoreState(state => state.schedules.data);
    const setSchedules = ServerContext.useStoreActions(actions => actions.schedules.setSchedules);

    useEffect(() => {
        clearFlashes('schedules');
        getServerSchedules(uuid)
            .then(schedules => setSchedules(schedules))
            .catch(error => {
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
                console.error(error);
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <ServerContentBlock title={'Schedules'}>
            <FlashMessageRender byKey={'schedules'} css={tw`mb-4`}/>
            {(!schedules.length && loading) ?
                <Spinner size={'large'} centered/>
                :
                <>
                    {
                        schedules.length === 0 ?
                            <p css={tw`text-sm text-center text-neutral-300`}>
                                There are no schedules configured for this server.
                            </p>
                            :
                            schedules.map(schedule => (
                                <GreyRowBox
                                    as={'a'}
                                    key={schedule.id}
                                    href={`${match.url}/${schedule.id}`}
                                    css={tw`cursor-pointer mb-2 flex-wrap`}
                                    onClick={(e: any) => {
                                        e.preventDefault();
                                        history.push(`${match.url}/${schedule.id}`);
                                    }}
                                >
                                    <ScheduleRow schedule={schedule}/>
                                </GreyRowBox>
                            ))
                    }
                    <Can action={'schedule.create'}>
                        <div css={tw`mt-8 flex justify-end`}>
                            <EditScheduleModal visible={visible} onModalDismissed={() => setVisible(false)}/>
                            <Button type={'button'} onClick={() => setVisible(true)}>
                                Create schedule
                            </Button>
                        </div>
                    </Can>
                </>
            }
        </ServerContentBlock>
    );
};
