import React, { useMemo, useState } from 'react';
import getServerSchedules, { Schedule } from '@/api/server/schedules/getServerSchedules';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { RouteComponentProps } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import { httpErrorToHuman } from '@/api/http';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';

interface Params {
    schedule?: string;
}

export default ({ history, match }: RouteComponentProps<Params>) => {
    const { id, uuid } = ServerContext.useStoreState(state => state.server.data!);
    const [ active, setActive ] = useState(0);
    const [ schedules, setSchedules ] = useState<Schedule[] | null>(null);
    const { clearFlashes, addError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useMemo(() => {
        clearFlashes('schedules');
        getServerSchedules(uuid)
            .then(schedules => setSchedules(schedules))
            .catch(error => {
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
                console.error(error);
            });
    }, [ setSchedules ]);

    const matched = useMemo(() => {
        return schedules?.find(schedule => schedule.id === active);
    }, [ active ]);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'schedules'} className={'mb-4'}/>
            {!schedules ?
                <Spinner size={'large'} centered={true}/>
                :
                schedules.map(schedule => (
                    <div
                        key={schedule.id}
                        onClick={() => setActive(schedule.id)}
                        className={'grey-row-box cursor-pointer'}
                    >
                        <ScheduleRow schedule={schedule}/>
                    </div>
                ))
            }
            {matched &&
            <EditScheduleModal
                schedule={matched}
                visible={true}
                appear={true}
                onDismissed={() => setActive(0)}
            />
            }
        </div>
    );
};
