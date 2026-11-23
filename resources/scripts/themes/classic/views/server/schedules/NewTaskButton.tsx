import { Dialog, NewButton, useTranslation, type ScheduleTaskForm, type ScheduleTaskValues } from '@/sdk';
import TaskDialog from './TaskDialog';

interface Props {
    form: ScheduleTaskForm;
    onSubmit: (values: ScheduleTaskValues) => Promise<boolean>;
    className?: string;
}

export default function NewTaskButton({ form, onSubmit, className }: Props) {
    const { t } = useTranslation();

    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <NewButton onClick={onClick} className={className}>
                    {t('New task')}
                </NewButton>
            )}
        >
            {(dialog) => <TaskDialog {...dialog} form={form} onSubmit={onSubmit} />}
        </Dialog.Trigger>
    );
}
