import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { useNavigate, useParams } from '@tanstack/react-router';
import { ListTodo } from 'lucide-react';
import { type Schedule, useServerSchedule } from '@/api/server/schedules/queries';
import Spinner from '@/components/elements/Spinner';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';
import NewTaskButton from '@/components/server/schedules/NewTaskButton';
import DeleteScheduleButton from '@/components/server/schedules/DeleteScheduleButton';
import Can from '@/components/elements/Can';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { cn } from '@/lib/cn';
import Button from '@/components/elements/Button';
import dayjs from '@/lib/dayjs';
import ScheduleCronRow from '@/components/server/schedules/ScheduleCronRow';
import RunScheduleButton from '@/components/server/schedules/RunScheduleButton';
import { useCurrentServerIdentifier, useCurrentServerUuid } from '@/api/server/queries';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import { Dialog } from '@/components/elements/dialog';
import DataTable from '@/components/elements/table/DataTable';
import { scheduleTaskColumns } from '@/components/server/schedules/ScheduleTaskTable';
import { relationshipData } from '@/api/relationships';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { usePermissions } from '@/plugins/usePermissions';

const CronBox = ({ title, value }: { title: string; value: string }) => (
    <div className='bg-card rounded-sm p-3'>
        <p className='text-muted-foreground text-sm'>{title}</p>
        <p className='text-xl font-medium text-foreground'>{value}</p>
    </div>
);

const ActivePill = ({ active }: { active: boolean }) => (
    <span
        className={cn(
            'rounded-full px-2 py-px text-xs ml-4 uppercase',
            active ? 'bg-success/90 text-success-foreground' : 'bg-destructive/90 text-destructive-foreground'
        )}
    >
        {active ? 'Active' : 'Inactive'}
    </span>
);

const ScheduleDetails = ({ schedule }: { schedule: Schedule }) => {
    const navigate = useNavigate();
    const id = useCurrentServerIdentifier() ?? '';
    const [canUpdate] = usePermissions('schedule.update');

    const tasks = useMemo(() => relationshipData(schedule.attributes.relationships?.tasks), [schedule]);
    const orderedTasks = useMemo(
        () => [...tasks].sort((a, b) => a.attributes.sequence_id - b.attributes.sequence_id),
        [tasks]
    );
    const columns = useMemo(() => scheduleTaskColumns(schedule), [schedule]);
    const table = useReactTable({
        data: orderedTasks,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (task) => String(task.attributes.id),
    });

    return (
        <PageContentBlock title='Schedules'>
            <ScheduleCronRow cron={schedule.attributes.cron} className='sm:hidden bg-card rounded-sm mb-4 p-3' />
            <div className='rounded-sm shadow-sm'>
                <div className='sm:flex items-center bg-muted p-3 sm:p-6 border-b-4 border-border rounded-t-sm'>
                    <div className='flex-1'>
                        <h3 className='flex items-center text-foreground text-2xl'>
                            {schedule.attributes.name}
                            {schedule.attributes.is_processing ? (
                                <span className='flex items-center rounded-full px-2 py-px text-xs ml-4 uppercase bg-popover text-foreground'>
                                    <Spinner />
                                    Processing
                                </span>
                            ) : (
                                <ActivePill active={schedule.attributes.is_active} />
                            )}
                        </h3>
                        <p className='mt-1 text-sm text-foreground'>
                            Last run at:&nbsp;
                            {schedule.attributes.last_run_at ? (
                                dayjs(schedule.attributes.last_run_at).format('MMM Do [at] h:mmA')
                            ) : (
                                <span className='text-muted-foreground'>n/a</span>
                            )}
                            <span className='ml-4 pl-4 border-l-4 border-border py-px'>
                                Next run at:&nbsp;
                                {schedule.attributes.next_run_at ? (
                                    dayjs(schedule.attributes.next_run_at).format('MMM Do [at] h:mmA')
                                ) : (
                                    <span className='text-muted-foreground'>n/a</span>
                                )}
                            </span>
                        </p>
                    </div>
                    <div className='flex sm:block mt-3 sm:mt-0'>
                        <Can action='schedule.update'>
                            <Dialog.Trigger
                                trigger={({ onClick }) => (
                                    <Button.Text className='flex-1 mr-4' onClick={onClick}>
                                        Edit
                                    </Button.Text>
                                )}
                            >
                                {({ open, onClose }) => (
                                    <EditScheduleModal open={open} schedule={schedule} onClose={onClose} />
                                )}
                            </Dialog.Trigger>
                            <NewTaskButton schedule={schedule} className='flex-1' />
                        </Can>
                    </div>
                </div>
                <div className='hidden sm:grid grid-cols-5 md:grid-cols-5 gap-4 mb-4 mt-4'>
                    <CronBox title='Minute' value={schedule.attributes.cron.minute} />
                    <CronBox title='Hour' value={schedule.attributes.cron.hour} />
                    <CronBox title='Day (Month)' value={schedule.attributes.cron.day_of_month} />
                    <CronBox title='Month' value={schedule.attributes.cron.month} />
                    <CronBox title='Day (Week)' value={schedule.attributes.cron.day_of_week} />
                </div>
                <DataTable
                    table={table}
                    emptyState={
                        <Empty className={emptyCompactClass}>
                            <EmptyHeader>
                                <EmptyMedia variant='icon'>
                                    <ListTodo />
                                </EmptyMedia>
                                <EmptyTitle>No tasks</EmptyTitle>
                                <EmptyDescription>
                                    Add a task to tell this schedule what to do when it runs.
                                </EmptyDescription>
                            </EmptyHeader>
                            {canUpdate && (
                                <EmptyContent>
                                    <NewTaskButton schedule={schedule} />
                                </EmptyContent>
                            )}
                        </Empty>
                    }
                    className='rounded-t-none'
                />
            </div>
            <div className='mt-6 flex sm:justify-end'>
                <Can action='schedule.delete'>
                    <DeleteScheduleButton
                        scheduleId={schedule.attributes.id}
                        onDeleted={() => navigate({ to: '/server/$id/schedules', params: { id } })}
                    />
                </Can>
                {orderedTasks.length > 0 && (
                    <Can action='schedule.update'>
                        <RunScheduleButton schedule={schedule} />
                    </Can>
                )}
            </div>
        </PageContentBlock>
    );
};

const ScheduleEditContainer = () => {
    const { scheduleId } = useParams({ strict: false });
    const uuid = useCurrentServerUuid() ?? '';
    const { data: schedule, error, isFetching, refetch } = useServerSchedule(uuid, Number(scheduleId));

    if (schedule) {
        return <ScheduleDetails schedule={schedule} />;
    }

    if (error && !isFetching) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <PageContentBlock title='Schedules'>
            <Spinner size='large' centered />
        </PageContentBlock>
    );
};

export default ScheduleEditContainer;
