import {
    Badge,
    Button,
    cn,
    Dialog,
    formatDate,
    Spinner,
    SpinnerOverlay,
    useTranslation,
    type ScheduleSummary,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import NewTaskButton from './NewTaskButton';
import ScheduleCronRow from './ScheduleCronRow';
import ScheduleDialog from './ScheduleDialog';

const CronBox = ({ title, value }: { title: string; value: string }) => (
    <div className='bg-card rounded-sm p-3'>
        <p className='text-muted-foreground text-sm'>{title}</p>
        <p className='text-xl font-medium text-foreground'>{value}</p>
    </div>
);

function StatusBadge({ schedule }: { schedule: ScheduleSummary }) {
    const { t } = useTranslation();

    if (schedule.processing) {
        return (
            <Badge className='ml-4 flex items-center rounded-full py-px uppercase'>
                <Spinner />
                {t('Processing')}
            </Badge>
        );
    }

    return (
        <Badge
            tone={schedule.active ? 'success' : 'danger'}
            className={cn('ml-4 rounded-full py-px uppercase', schedule.active ? 'bg-success/90' : 'bg-destructive/90')}
        >
            {schedule.active ? t('Active') : t('Inactive')}
        </Badge>
    );
}

function RunTime({ at }: { at: string | null }) {
    const { t } = useTranslation();

    return at ? formatDate(at, 'lll') : <span className='text-muted-foreground'>{t('n/a')}</span>;
}

/** The schedule's header, cron and actions; `children` (its tasks) render inside the box below the cron. */
export default function ScheduleDetails({ model, className, children }: ViewProps<'server.schedule'>) {
    const { t } = useTranslation();
    const { schedule, actions } = model;

    return (
        <div className={className}>
            <ScheduleCronRow cron={schedule.cron} className='sm:hidden bg-card rounded-sm mb-4 p-3' />
            <div className='rounded-sm shadow-sm'>
                <div className='sm:flex items-center bg-muted p-3 sm:p-6 border-b-4 border-border rounded-t-sm'>
                    <div className='flex-1'>
                        <h3 className='flex items-center text-foreground text-2xl'>
                            {schedule.name}
                            <StatusBadge schedule={schedule} />
                        </h3>
                        <p className='mt-1 text-sm text-foreground'>
                            {t('Last run at:')}
                            {'\u00a0'}
                            <RunTime at={schedule.lastRunAt} />
                            <span className='ml-4 pl-4 border-l-4 border-border py-px'>
                                {t('Next run at:')}
                                {'\u00a0'}
                                <RunTime at={schedule.nextRunAt} />
                            </span>
                        </p>
                    </div>
                    <div className='flex sm:block mt-3 sm:mt-0'>
                        {model.canUpdate && (
                            <>
                                <Dialog.Trigger
                                    trigger={({ onClick }) => (
                                        <Button.Text className='flex-1 mr-4' onClick={onClick}>
                                            {t('Edit')}
                                        </Button.Text>
                                    )}
                                >
                                    {(dialog) => (
                                        <ScheduleDialog
                                            {...dialog}
                                            values={model.values}
                                            editing
                                            onSubmit={actions.update}
                                        />
                                    )}
                                </Dialog.Trigger>
                                <NewTaskButton form={model.taskForm} onSubmit={actions.createTask} className='flex-1' />
                            </>
                        )}
                    </div>
                </div>
                <div className='hidden sm:grid grid-cols-5 md:grid-cols-5 gap-4 mb-4 mt-4'>
                    <CronBox title={t('Minute')} value={schedule.cron.minute} />
                    <CronBox title={t('Hour')} value={schedule.cron.hour} />
                    <CronBox title={t('Day (Month)')} value={schedule.cron.dayOfMonth} />
                    <CronBox title={t('Month')} value={schedule.cron.month} />
                    <CronBox title={t('Day (Week)')} value={schedule.cron.dayOfWeek} />
                </div>
                {children}
            </div>
            <div className='mt-6 flex sm:justify-end'>
                {model.canDelete && (
                    <Dialog.ConfirmTrigger
                        title={t('Delete Schedule')}
                        confirm={t('Delete')}
                        onConfirmed={async (_event, close) => {
                            await actions.remove();
                            close();
                        }}
                        trigger={({ onClick }) => (
                            <Button.Danger
                                isSecondary
                                className='flex-1 sm:flex-none mr-4 border-transparent'
                                onClick={onClick}
                            >
                                {t('Delete')}
                            </Button.Danger>
                        )}
                    >
                        <SpinnerOverlay visible={model.deleting} />
                        {t('All tasks will be removed and any running processes will be terminated.')}
                    </Dialog.ConfirmTrigger>
                )}
                {model.taskCount > 0 && model.canUpdate && (
                    <>
                        <SpinnerOverlay visible={model.running} size='large' />
                        <Button
                            isSecondary
                            className='flex-1 sm:flex-none'
                            disabled={schedule.processing || model.running}
                            onClick={() => void actions.run()}
                        >
                            {t('Run Now')}
                        </Button>
                    </>
                )}
            </div>
        </div>
    );
}
