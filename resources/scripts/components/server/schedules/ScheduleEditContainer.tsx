import React, { useEffect, useState } from 'react';
import { RouteComponentProps } from 'react-router-dom';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import getServerSchedule from '@/api/server/schedules/getServerSchedule';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { httpErrorToHuman } from '@/api/http';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import ScheduleTaskRow from '@/components/server/schedules/ScheduleTaskRow';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';
import NewTaskButton from '@/components/server/schedules/NewTaskButton';
import DeleteScheduleButton from '@/components/server/schedules/DeleteScheduleButton';
import Can from '@/components/elements/Can';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import GreyRowBox from '@/components/elements/GreyRowBox';

interface Params {
    id: string;
}

interface State {
    schedule?: Schedule;
}

export default ({ match, history, location: { state } }: RouteComponentProps<Params, Record<string, unknown>, State>) => {
    const { id, uuid } = useServer();
    const { clearFlashes, addError } = useFlash();
    const [ isLoading, setIsLoading ] = useState(true);
    const [ showEditModal, setShowEditModal ] = useState(false);

    const schedule = ServerContext.useStoreState(st => st.schedules.data.find(s => s.id === state.schedule?.id), [ match ]);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    useEffect(() => {
        if (schedule?.id === Number(match.params.id)) {
            setIsLoading(false);
            return;
        }

        clearFlashes('schedules');
        getServerSchedule(uuid, Number(match.params.id))
            .then(schedule => appendSchedule(schedule))
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
            })
            .then(() => setIsLoading(false));
    }, [ match ]);

    return (
        <PageContentBlock>
            <FlashMessageRender byKey={'schedules'} css={tw`mb-4`}/>
            {!schedule || isLoading ?
                <Spinner size={'large'} centered/>
                :
                <>
                    <GreyRowBox>
                        <ScheduleRow schedule={schedule}/>
                    </GreyRowBox>
                    <EditScheduleModal
                        visible={showEditModal}
                        schedule={schedule}
                        onDismissed={() => setShowEditModal(false)}
                    />
                    <div css={tw`flex items-center mt-8 mb-4`}>
                        <div css={tw`flex-1`}>
                            <h2 css={tw`text-2xl`}>Configured Tasks</h2>
                        </div>
                    </div>
                    {schedule.tasks.length > 0 ?
                        <>
                            {
                                schedule.tasks
                                    .sort((a, b) => a.sequenceId - b.sequenceId)
                                    .map(task => (
                                        <ScheduleTaskRow key={task.id} task={task} schedule={schedule}/>
                                    ))
                            }
                            {schedule.tasks.length > 1 &&
                            <p css={tw`text-xs text-neutral-400`}>
                                Task delays are relative to the previous task in the listing.
                            </p>
                            }
                        </>
                        :
                        <p css={tw`text-sm text-neutral-400`}>
                            There are no tasks configured for this schedule.
                        </p>
                    }
                    <div css={tw`mt-8 flex justify-end`}>
                        <Can action={'schedule.delete'}>
                            <DeleteScheduleButton
                                scheduleId={schedule.id}
                                onDeleted={() => history.push(`/server/${id}/schedules`)}
                            />
                        </Can>
                        <Can action={'schedule.update'}>
                            <Button css={tw`mr-4`} onClick={() => setShowEditModal(true)}>
                                Edit
                            </Button>
                            <NewTaskButton schedule={schedule}/>
                        </Can>
                    </div>
                </>
            }
        </PageContentBlock>
    );
};
