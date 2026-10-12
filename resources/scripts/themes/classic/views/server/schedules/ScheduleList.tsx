import { useMemo } from 'react';
import { CalendarClock } from 'lucide-react';
import {
    actionsColumn,
    Badge,
    cn,
    Dialog,
    EditLinkAction,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    formatDate,
    fromNow,
    ListToolbar,
    NewButton,
    PanelLink,
    RowActions,
    Spinner,
    Table,
    type ScheduleListModel,
    type ScheduleSummary,
    type TableColumn,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import ScheduleCronRow from './ScheduleCronRow';
import ScheduleDialog from './ScheduleDialog';

const scheduleStatus = (schedule: ScheduleSummary): string => {
    if (schedule.processing) {
        return 'processing';
    }

    return schedule.active ? 'active' : 'inactive';
};

const scheduleDestination = (model: ScheduleListModel, schedule: ScheduleSummary) =>
    ({
        to: '/server/$id/schedules/$scheduleId',
        params: { id: model.serverId, scheduleId: String(schedule.id) },
    }) as const;

function columns(model: ScheduleListModel): TableColumn<ScheduleSummary>[] {
    return [
        {
            id: 'name',
            label: 'Schedule',
            sortValue: (schedule) => schedule.name,
            cell: (schedule) => (
                <div className='min-w-40'>
                    <PanelLink
                        destination={scheduleDestination(model, schedule)}
                        className='font-medium hover:text-primary'
                    >
                        {schedule.name}
                    </PanelLink>
                    <p className='mt-0.5 text-xs text-muted-foreground md:hidden'>
                        Last run {schedule.lastRunAt ? fromNow(schedule.lastRunAt) : 'never'}
                    </p>
                </div>
            ),
        },
        {
            id: 'cron',
            label: 'Cron',
            cell: (schedule) => <ScheduleCronRow cron={schedule.cron} />,
            headerClassName: 'hidden lg:table-cell',
            cellClassName: 'hidden lg:table-cell',
        },
        {
            id: 'last_run_at',
            label: 'Last run',
            sortValue: (schedule) => schedule.lastRunAt ?? '',
            cell: (schedule) => (
                <span className='whitespace-nowrap text-xs text-muted-foreground'>
                    {schedule.lastRunAt ? formatDate(schedule.lastRunAt, 'MMM D, YYYY HH:mm') : 'Never'}
                </span>
            ),
            headerClassName: 'hidden md:table-cell w-40',
            cellClassName: 'hidden md:table-cell w-40',
        },
        {
            id: 'status',
            label: 'Status',
            sortValue: scheduleStatus,
            cell: (schedule) => {
                const status = scheduleStatus(schedule);

                return (
                    <Badge
                        tone={status === 'active' ? 'success' : 'neutral'}
                        className={cn(
                            'inline-flex rounded-full font-medium capitalize',
                            status === 'active' && 'bg-success/90'
                        )}
                    >
                        {status}
                    </Badge>
                );
            },
            headerClassName: 'w-28',
            cellClassName: 'w-28',
        },
        actionsColumn(1, (schedule) =>
            model.canUpdate ? (
                <RowActions>
                    <EditLinkAction
                        aria-label={`Edit ${schedule.name}`}
                        destination={scheduleDestination(model, schedule)}
                    />
                </RowActions>
            ) : null
        ),
    ];
}

function CreateScheduleButton({ model }: { model: ScheduleListModel }) {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New schedule</NewButton>}>
            {(dialog) => <ScheduleDialog {...dialog} values={model.defaults} editing={false} onSubmit={model.create} />}
        </Dialog.Trigger>
    );
}

export default function ScheduleList({ model }: ViewProps<'server.schedules'>) {
    const tableColumns = useMemo(() => columns(model), [model]);

    if (model.loading) {
        return <Spinner size='large' centered />;
    }

    return (
        <>
            {model.canCreate && (
                <ListToolbar>
                    <CreateScheduleButton model={model} />
                </ListToolbar>
            )}
            <Table
                rows={model.schedules}
                columns={tableColumns}
                keyOf={(schedule) => String(schedule.id)}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <CalendarClock />
                            </EmptyMedia>
                            <EmptyTitle>No schedules</EmptyTitle>
                            <EmptyDescription>
                                Schedules run commands, power actions and backups automatically.
                            </EmptyDescription>
                        </EmptyHeader>
                        {model.canCreate && (
                            <EmptyContent>
                                <CreateScheduleButton model={model} />
                            </EmptyContent>
                        )}
                    </Empty>
                }
            />
        </>
    );
}
