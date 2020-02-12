import React, { useMemo, useState } from 'react';
import getServerSchedules, { Schedule } from '@/api/server/schedules/getServerSchedules';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { Link, RouteComponentProps } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import { httpErrorToHuman } from '@/api/http';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';

interface Params {
    schedule?: string;
}

export default ({ history, match, location: { hash } }: RouteComponentProps<Params>) => {
    const { id, uuid } = ServerContext.useStoreState(state => state.server.data!);
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

    const matched = (schedules || []).find(schedule => schedule.id === Number(hash.match(/\d+$/) || 0));

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'schedules'} className={'mb-4'}/>
            {!schedules ?
                <Spinner size={'large'} centered={true}/>
                :
                schedules.map(schedule => (
                    <Link
                        key={schedule.id}
                        to={`/server/${id}/schedules/#/schedule/${schedule.id}`}
                        className={'grey-row-box'}
                    >
                        <ScheduleRow schedule={schedule}/>
                    </Link>
                ))
            }
            {matched &&
            <EditScheduleModal
                schedule={matched}
                visible={true}
                appear={true}
                onDismissed={() => history.push(match.url)}
            />
            }
        </div>
    );
};
