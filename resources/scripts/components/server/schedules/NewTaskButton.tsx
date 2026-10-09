import { type Schedule } from '@/api/server/schedules/queries';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import { NewButton } from '@/components/elements/NewButton';
import { Dialog } from '@/components/elements/dialog';
import type { WithClassname } from '@/components/types';

interface Props extends WithClassname {
    schedule: Schedule;
}

const NewTaskButton = ({ schedule, className }: Props) => (
    <Dialog.Trigger
        trigger={({ onClick }) => (
            <NewButton onClick={onClick} className={className}>
                New task
            </NewButton>
        )}
    >
        {(dialog) => <TaskDetailsModal schedule={schedule} {...dialog} />}
    </Dialog.Trigger>
);

export default NewTaskButton;
