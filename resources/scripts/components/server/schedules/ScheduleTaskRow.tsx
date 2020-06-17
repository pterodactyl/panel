import React, { useState } from 'react';
import { Schedule, Task } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faCode } from '@fortawesome/free-solid-svg-icons/faCode';
import { faToggleOn } from '@fortawesome/free-solid-svg-icons/faToggleOn';
import { faArchive } from '@fortawesome/free-solid-svg-icons/faArchive';
import ConfirmTaskDeletionModal from '@/components/server/schedules/ConfirmTaskDeletionModal';
import deleteScheduleTask from '@/api/server/schedules/deleteScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import { faPencilAlt } from '@fortawesome/free-solid-svg-icons/faPencilAlt';
import Can from '@/components/elements/Can';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';

interface Props {
    schedule: Schedule;
    task: Task;
}

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

    return (
        <div className={'flex items-center bg-neutral-700 border border-neutral-600 mb-2 px-6 py-4 rounded'}>
            <SpinnerOverlay visible={isLoading} fixed={true} size={'large'}/>
            {isEditing && <TaskDetailsModal
                schedule={schedule}
                task={task}
                onDismissed={() => setIsEditing(false)}
            />}
            <ConfirmTaskDeletionModal
                visible={visible}
                onDismissed={() => setVisible(false)}
                onConfirmed={() => onConfirmDeletion()}
            />
            <FontAwesomeIcon icon={task.action === 'command' ? faCode : (task.action === 'backup' ? faArchive : faToggleOn) } className={'text-lg text-white'}/>
            <div className={'flex-1'}>
                <p className={'ml-6 text-neutral-300 mb-2 uppercase text-xs'}>
                    {task.action === 'command' ? "Send command" : (task.action === 'backup' ? "Create Backup" : "Send power action") }
                </p>
                <code className={'ml-6 font-mono bg-neutral-800 rounded py-1 px-2 text-sm'}>
                    {task.payload}
                </code>
            </div>
            {task.sequenceId > 1 &&
            <div className={'mr-6'}>
                <p className={'text-center mb-1'}>
                    {task.timeOffset}s
                </p>
                <p className={'text-neutral-300 uppercase text-2xs'}>
                    Delay Run By
                </p>
            </div>
            }
            <Can action={'schedule.update'}>
                <button
                    type={'button'}
                    aria-label={'Edit scheduled task'}
                    className={'block text-sm p-2 text-neutral-500 hover:text-neutral-100 transition-colors duration-150 mr-4'}
                    onClick={() => setIsEditing(true)}
                >
                    <FontAwesomeIcon icon={faPencilAlt}/>
                </button>
            </Can>
            <Can action={'schedule.update'}>
                <button
                    type={'button'}
                    aria-label={'Delete scheduled task'}
                    className={'block text-sm p-2 text-neutral-500 hover:text-red-600 transition-colors duration-150'}
                    onClick={() => setVisible(true)}
                >
                    <FontAwesomeIcon icon={faTrashAlt}/>
                </button>
            </Can>
        </div>
    );
};
