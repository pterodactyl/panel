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
    useTranslation,
    type ScheduleListModel,
    type ScheduleSummary,
    type TableColumn,
    type Translation,
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

const statusLabel = (status: string, t: Translation['t']): string => {
    if (status === 'processing') {
        return t('processing');
    }

    return status === 'active' ? t('active') : t('inactive');
};

function columns(model: ScheduleListModel, t: Translation['t']): TableColumn<ScheduleSummary>[] {
    return [
        {
            id: 'name',
            label: t('Schedule'),
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
                        {schedule.lastRunAt
                            ? t('Last run {{time}}', { time: fromNow(schedule.lastRunAt) })
                            : t('Last run never')}
                    </p>
                </div>
            ),
        },
        {
            id: 'cron',
            label: t('Cron'),
            cell: (schedule) => <ScheduleCronRow cron={schedule.cron} />,
            headerClassName: 'hidden lg:table-cell',
            cellClassName: 'hidden lg:table-cell',
        },
        {
            id: 'last_run_at',
            label: t('Last run'),
            sortValue: (schedule) => schedule.lastRunAt ?? '',
            cell: (schedule) => (
                <span className='whitespace-nowrap text-xs text-muted-foreground'>
                    {schedule.lastRunAt ? formatDate(schedule.lastRunAt, 'lll') : t('Never')}
                </span>
            ),
            headerClassName: 'hidden md:table-cell w-40',
            cellClassName: 'hidden md:table-cell w-40',
        },
        {
            id: 'status',
            label: t('Status'),
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
                        {statusLabel(status, t)}
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
                        aria-label={t('Edit {{name}}', { name: schedule.name })}
                        destination={scheduleDestination(model, schedule)}
                    />
                </RowActions>
            ) : null
        ),
    ];
}

function CreateScheduleButton({ model }: { model: ScheduleListModel }) {
    const { t } = useTranslation();

    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>{t('New schedule')}</NewButton>}>
            {(dialog) => <ScheduleDialog {...dialog} values={model.defaults} editing={false} onSubmit={model.create} />}
        </Dialog.Trigger>
    );
}

export default function ScheduleList({ model, className }: ViewProps<'server.schedules'>) {
    const { t } = useTranslation();
    const tableColumns = useMemo(() => columns(model, t), [model, t]);

    if (model.loading) {
        return (
            <div className={className}>
                <Spinner size='large' centered />
            </div>
        );
    }

    return (
        <div className={className}>
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
                            <EmptyTitle>{t('No schedules')}</EmptyTitle>
                            <EmptyDescription>
                                {t('Schedules run commands, power actions and backups automatically.')}
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
        </div>
    );
}
