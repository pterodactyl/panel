import React, { useState } from 'react';
import { Schedule, Task } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCode, faFileArchive, faPencilAlt, faToggleOn, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import deleteScheduleTask from '@/api/server/schedules/deleteScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import Can from '@/components/elements/Can';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import ConfirmationModal from '@/components/elements/ConfirmationModal';

interface Props {
    schedule: Schedule;
    task: Task;
}

const getActionDetails = (action: string): [ string, any ] => {
    switch (action) {
        case 'command':
            return [ 'Send Command', faCode ];
        case 'power':
            return [ 'Send Power Action', faToggleOn ];
        case 'backup':
            return [ 'Create Backup', faFileArchive ];
        default:
            return [ 'Unknown Action', faCode ];
    }
};

export default ({ schedule, task }: Props) => {
    const { uuid } = useServer();
    const { clearFlashes, addError } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const [ isLoading, setIsLoading ] = useState(false);
    const [ isEditing, setIsEditing ] = useState(false);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    const onConfirmDeletion = () => {
        setIsLoading(true);
        clearFlashes('schedules');
        deleteScheduleTask(uuid, schedule.id, task.id)
            .then(() => appendSchedule({
                ...schedule,
                tasks: schedule.tasks.filter(t => t.id !== task.id),
            }))
            .catch(error => {
                console.error(error);
                setIsLoading(false);
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
            });
    };

    const [ title, icon ] = getActionDetails(task.action);

    return (
        <div css={tw`flex items-center bg-neutral-700 border border-neutral-600 mb-2 px-6 py-4 rounded`}>
            <SpinnerOverlay visible={isLoading} fixed size={'large'}/>
            {isEditing && <TaskDetailsModal
                schedule={schedule}
                task={task}
                onDismissed={() => setIsEditing(false)}
            />}
            <ConfirmationModal
                title={'Confirm task deletion'}
                buttonText={'Delete Task'}
                onConfirmed={onConfirmDeletion}
                visible={visible}
                onDismissed={() => setVisible(false)}
            >
                Are you sure you want to delete this task? This action cannot be undone.
            </ConfirmationModal>
            <FontAwesomeIcon icon={icon} css={tw`text-lg text-white`}/>
            <div css={tw`flex-1`}>
                <p css={tw`ml-6 text-neutral-300 uppercase text-xs`}>
                    {title}
                </p>
                {task.payload &&
                <div css={tw`ml-6 mt-2`}>
                    {task.action === 'backup' &&
                    <p css={tw`text-xs uppercase text-neutral-400 mb-1`}>Ignoring files & folders:</p>}
                    <div css={tw`font-mono bg-neutral-800 rounded py-1 px-2 text-sm w-auto whitespace-pre inline-block`}>
                        {task.payload}
                    </div>
                </div>
                }
            </div>
            {task.sequenceId > 1 &&
            <div css={tw`mr-6`}>
                <p css={tw`text-center mb-1`}>
                    {task.timeOffset}s
                </p>
                <p css={tw`text-neutral-300 uppercase text-2xs`}>
                    Delay Run By
                </p>
            </div>
            }
            <Can action={'schedule.update'}>
                <button
                    type={'button'}
                    aria-label={'Edit scheduled task'}
                    css={tw`block text-sm p-2 text-neutral-500 hover:text-neutral-100 transition-colors duration-150 mr-4`}
                    onClick={() => setIsEditing(true)}
                >
                    <FontAwesomeIcon icon={faPencilAlt}/>
                </button>
            </Can>
            <Can action={'schedule.update'}>
                <button
                    type={'button'}
                    aria-label={'Delete scheduled task'}
                    css={tw`block text-sm p-2 text-neutral-500 hover:text-red-600 transition-colors duration-150`}
                    onClick={() => setVisible(true)}
                >
                    <FontAwesomeIcon icon={faTrashAlt}/>
                </button>
            </Can>
        </div>
    );
};
