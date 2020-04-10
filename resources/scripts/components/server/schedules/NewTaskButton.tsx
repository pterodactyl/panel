import React, { useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';

interface Props {
    schedule: Schedule;
}

export default ({ schedule }: Props) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            {visible &&
            <TaskDetailsModal
                schedule={schedule}
                onDismissed={() => setVisible(false)}
            />
            }
            <button className={'btn btn-primary btn-sm'} onClick={() => setVisible(true)}>
                New Task
            </button>
        </>
    );
};
