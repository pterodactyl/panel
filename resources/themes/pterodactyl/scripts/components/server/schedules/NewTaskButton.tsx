import React, { useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import Button from '@/components/elements/Button';

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
            <Button onClick={() => setVisible(true)}>
                New Task
            </Button>
        </>
    );
};
