import React, { useEffect, useState } from 'react';
import { RouteComponentProps } from 'react-router-dom';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import getServerSchedule from '@/api/server/schedules/getServerSchedule';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import ScheduleTaskRow from '@/components/server/schedules/ScheduleTaskRow';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';

interface Params {
    id: string;
}

interface State {
    schedule?: Schedule;
}

export default ({ match, location: { state } }: RouteComponentProps<Params, {}, State>) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const [ isLoading, setIsLoading ] = useState(true);
    const [ showEditModal, setShowEditModal ] = useState(false);
    const [ schedule, setSchedule ] = useState<Schedule | undefined>(state?.schedule);
    const { clearFlashes, addError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        if (schedule?.id === Number(match.params.id)) {
            setIsLoading(false);
            return;
        }

        clearFlashes('schedules');
        getServerSchedule(uuid, Number(match.params.id))
            .then(schedule => setSchedule(schedule))
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
            })
            .then(() => setIsLoading(false));
    }, [ schedule, match ]);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'schedules'} className={'mb-4'}/>
            {!schedule || isLoading ?
                <Spinner size={'large'} centered={true}/>
                :
                <>
                    <div className={'grey-row-box'}>
                        <ScheduleRow schedule={schedule}/>
                    </div>
                    <EditScheduleModal
                        visible={showEditModal}
                        schedule={schedule}
                        onDismissed={() => setShowEditModal(false)}
                    />
                    <div className={'flex items-center my-4'}>
                        <div className={'flex-1'}>
                            <h2>Schedule Tasks</h2>
                        </div>
                        <button className={'btn btn-secondary btn-sm'} onClick={() => setShowEditModal(true)}>
                            Edit
                        </button>
                        <button className={'btn btn-primary btn-sm ml-4'}>
                            New Task
                        </button>
                    </div>
                    {schedule?.tasks.length > 0 ?
                        <>
                            {
                                schedule.tasks
                                    .sort((a, b) => a.sequenceId - b.sequenceId)
                                    .map(task => (
                                        <div
                                            key={task.id}
                                            className={'bg-neutral-700 border border-neutral-600 mb-2 px-6 py-4 rounded'}
                                        >
                                            <ScheduleTaskRow task={task}/>
                                        </div>
                                    ))
                            }
                            {schedule.tasks.length > 1 &&
                            <p className={'text-xs text-neutral-400'}>
                                Task delays are relative to the previous task in the listing.
                            </p>
                            }
                        </>
                        :
                        <p className={'text-sm text-neutral-400'}>
                            There are no tasks configured for this schedule. Consider adding a new one using the
                            button above.
                        </p>
                    }
                </>
            }
        </div>
    );
};
