import React, { useMemo, useState } from 'react';
import getServerSchedules, { Schedule } from '@/api/server/schedules/getServerSchedules';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { RouteComponentProps, Link } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import { httpErrorToHuman } from '@/api/http';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

export default ({ match, history }: RouteComponentProps) => {
    const { uuid } = ServerContext.useStoreState(state => state.server.data!);
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

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'schedules'} className={'mb-4'}/>
            {!schedules ?
                <Spinner size={'large'} centered={true}/>
                :
                schedules.map(schedule => (
                    <a
                        key={schedule.id}
                        href={`${match.url}/${schedule.id}`}
                        className={'grey-row-box cursor-pointer'}
                        onClick={e => {
                            e.preventDefault();
                            history.push(`${match.url}/${schedule.id}`, { schedule });
                        }}
                    >
                        <ScheduleRow schedule={schedule}/>
                    </a>
                ))
            }
        </div>
    );
};
