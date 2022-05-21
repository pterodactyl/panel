import tw from 'twin.macro';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';

interface Props {
    schedule: Schedule;
}

export default ({ schedule }: Props) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <TaskDetailsModal schedule={schedule} visible={visible} onModalDismissed={() => setVisible(false)}/>
            <Button onClick={() => setVisible(true)} css={tw`flex-1`}>
                New Task
            </Button>
        </>
    );
};
