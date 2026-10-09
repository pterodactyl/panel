import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import {
    executeServerScheduleInput,
    type Schedule,
    useTriggerServerScheduleExecution,
} from '@/api/server/schedules/queries';
import { useCurrentServerUuid } from '@/api/server/queries';

const RunScheduleButton = ({ schedule }: { schedule: Schedule }) => {
    const uuid = useCurrentServerUuid()!;
    const triggerSchedule = useTriggerServerScheduleExecution(schedule);

    const onTriggerExecute = async () => {
        try {
            await triggerSchedule.mutateAsync(executeServerScheduleInput(uuid, schedule));
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return (
        <>
            <SpinnerOverlay visible={triggerSchedule.isPending} size='large' />
            <Button
                isSecondary
                className='flex-1 sm:flex-none'
                disabled={schedule.attributes.is_processing || triggerSchedule.isPending}
                onClick={onTriggerExecute}
            >
                Run Now
            </Button>
        </>
    );
};

export default RunScheduleButton;
