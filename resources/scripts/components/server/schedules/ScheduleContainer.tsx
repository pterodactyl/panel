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
import Can from '@/components/elements/Can';

export default ({ match, history }: RouteComponentProps) => {
    const { uuid } = ServerContext.useStoreState(state => state.server.data!);
    const [ schedules, setSchedules ] = useState<Schedule[] | null>(null);
    const [ visible, setVisible ] = useState(false);
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
                <>
                    {
                        schedules.length === 0 ?
                            <p className={'text-sm text-center text-neutral-400'}>
                                There are no schedules configured for this server.
                            </p>
                            :
                            schedules.map(schedule => (
                                <a
                                    key={schedule.id}
                                    href={`${match.url}/${schedule.id}`}
                                    className={'grey-row-box cursor-pointer mb-2'}
                                    onClick={e => {
                                        e.preventDefault();
                                        history.push(`${match.url}/${schedule.id}`, { schedule });
                                    }}
                                >
                                    <ScheduleRow schedule={schedule}/>
                                </a>
                            ))
                    }
                    <Can action={'schedule.create'}>
                        <div className={'mt-8 flex justify-end'}>
                            {visible && <EditScheduleModal
                                appear={true}
                                visible={true}
                                onScheduleUpdated={schedule => setSchedules(s => [ ...(s || []), schedule ])}
                                onDismissed={() => setVisible(false)}
                            />}
                            <button
                                type={'button'}
                                className={'btn btn-sm btn-primary'}
                                onClick={() => setVisible(true)}
                            >
                                Create schedule
                            </button>
                        </div>
                    </Can>
                </>
            }
        </div>
    );
};
