import React, { useState } from 'react';
import { Task } from '@/api/server/schedules/getServerSchedules';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';

interface Props {
    scheduleId: number;
    onTaskAdded: (task: Task) => void;
}

export default ({ scheduleId, onTaskAdded }: Props) => {
    const [visible, setVisible] = useState(false);

    return (
        <>
            {visible &&
                <TaskDetailsModal
                    scheduleId={scheduleId}
                    onDismissed={task => {
                        task && onTaskAdded(task);
                        setVisible(false);
                    }}
                />
            }
            <button className={'btn btn-primary btn-sm'} onClick={() => setVisible(true)}>
                New Task
            </button>
        </>
    );
};
