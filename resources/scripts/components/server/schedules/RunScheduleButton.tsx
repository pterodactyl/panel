import { useCallback, useState } from 'react';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Button } from '@/components/elements/button/index';
import triggerScheduleExecution from '@/api/server/schedules/triggerScheduleExecution';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import { Schedule } from '@/api/server/schedules/getServerSchedules';

const RunScheduleButton = ({ schedule }: { schedule: Schedule }) => {
    const [loading, setLoading] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    const onTriggerExecute = useCallback(() => {
        clearFlashes('schedule');
        setLoading(true);
        triggerScheduleExecution(id, schedule.id)
            .then(() => {
                setLoading(false);
                appendSchedule({ ...schedule, isProcessing: true });
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: 'schedules' });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <>
            <SpinnerOverlay visible={loading} size={'large'} />
            <Button
                variant={Button.Variants.Secondary}
                className={'flex-1 sm:flex-none'}
                disabled={schedule.isProcessing}
                onClick={onTriggerExecute}
            >
                Run Now
            </Button>
        </>
    );
};

export default RunScheduleButton;
