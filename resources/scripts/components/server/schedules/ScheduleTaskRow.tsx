import React, { useState } from 'react';
import { Schedule, Task } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowCircleDown,
    faClock,
    faCode,
    faFileArchive,
    faPencilAlt,
    faToggleOn,
    faTrashAlt,
} from '@fortawesome/free-solid-svg-icons';
import deleteScheduleTask from '@/api/server/schedules/deleteScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import Can from '@/components/elements/Can';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import Icon from '@/components/elements/Icon';

interface Props {
    schedule: Schedule;
    task: Task;
}

const getActionDetails = (action: string): [string, any] => {
    switch (action) {
        case 'command':
            return ['Send Command', faCode];
        case 'power':
            return ['Send Power Action', faToggleOn];
        case 'backup':
            return ['Create Backup', faFileArchive];
        case 'delete-files':
            return ['Delete Files', faTrashAlt];
        default:
            return ['Unknown Action', faCode];
    }
};

export default ({ schedule, task }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, addError } = useFlash();
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const appendSchedule = ServerContext.useStoreActions((actions) => actions.schedules.appendSchedule);

    const onConfirmDeletion = () => {
        setIsLoading(true);
        clearFlashes('schedules');
        deleteScheduleTask(uuid, schedule.id, task.id)
            .then(() =>
                appendSchedule({
                    ...schedule,
                    tasks: schedule.tasks.filter((t) => t.id !== task.id),
                })
            )
            .catch((error) => {
                console.error(error);
                setIsLoading(false);
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
            });
    };

    const [title, icon] = getActionDetails(task.action);

    return (
        <div css={tw`items-center p-3 border-b sm:flex sm:p-6 border-neutral-800`}>
            <SpinnerOverlay visible={isLoading} fixed size={'large'} />
            <TaskDetailsModal
                schedule={schedule}
                task={task}
                visible={isEditing}
                onModalDismissed={() => setIsEditing(false)}
            />
            <ConfirmationModal
                title={'Confirm task deletion'}
                buttonText={'Delete Task'}
                onConfirmed={onConfirmDeletion}
                visible={visible}
                onModalDismissed={() => setVisible(false)}
            >
                Are you sure you want to delete this task? This action cannot be undone.
            </ConfirmationModal>
            <FontAwesomeIcon icon={icon} css={tw`hidden text-lg text-white md:block`} />
            <div css={tw`flex-none w-full overflow-x-auto sm:flex-1 sm:w-auto`}>
                <p css={tw`text-sm uppercase md:ml-6 text-neutral-200`}>{title}</p>
                {task.payload && (
                    <div css={tw`mt-2 md:ml-6`}>
                        {task.action === 'backup' && (
                            <p css={tw`mb-1 text-xs uppercase text-neutral-400`}>Ignoring files & folders:</p>
                        )}
                        <div
                            css={tw`inline-block w-auto px-2 py-1 font-mono text-sm break-all whitespace-pre-wrap rounded bg-neutral-800`}
                        >
                            {task.payload}
                        </div>
                    </div>
                )}
            </div>
            <div css={tw`flex items-center w-full mt-3 sm:mt-0 sm:w-auto`}>
                {task.continueOnFailure && (
                    <div css={tw`mr-6`}>
                        <div css={tw`flex items-center px-2 py-1 text-sm text-yellow-800 bg-yellow-500 rounded-full`}>
                            <Icon icon={faArrowCircleDown} css={tw`w-3 h-3 mr-2`} />
                            Continues on Failure
                        </div>
                    </div>
                )}
                {task.sequenceId > 1 && task.timeOffset > 0 && (
                    <div css={tw`mr-6`}>
                        <div css={tw`flex items-center px-2 py-1 text-sm rounded-full bg-neutral-500`}>
                            <Icon icon={faClock} css={tw`w-3 h-3 mr-2`} />
                            {task.timeOffset}s later
                        </div>
                    </div>
                )}
                <Can action={'schedule.update'}>
                    <button
                        type={'button'}
                        aria-label={'Edit scheduled task'}
                        css={tw`block p-2 ml-auto mr-4 text-sm transition-colors duration-150 text-neutral-500 hover:text-neutral-100 sm:ml-0`}
                        onClick={() => setIsEditing(true)}
                    >
                        <FontAwesomeIcon icon={faPencilAlt} />
                    </button>
                </Can>
                <Can action={'schedule.update'}>
                    <button
                        type={'button'}
                        aria-label={'Delete scheduled task'}
                        css={tw`block p-2 text-sm transition-colors duration-150 text-neutral-500 hover:text-red-600`}
                        onClick={() => setVisible(true)}
                    >
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </button>
                </Can>
            </div>
        </div>
    );
};
