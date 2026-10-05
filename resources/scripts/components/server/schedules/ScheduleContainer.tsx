import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { CalendarClock } from 'lucide-react';
import { useServerSchedules } from '@/api/server/schedules/queries';
import Spinner from '@/components/elements/Spinner';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewButton } from '@/components/elements/NewButton';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { useCurrentServer } from '@/api/server/queries';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import { Dialog } from '@/components/elements/dialog';
import DataTable from '@/components/elements/table/DataTable';
import { scheduleColumns } from '@/components/server/schedules/ScheduleTable';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { usePermissions } from '@/plugins/usePermissions';

const CreateScheduleButton = () => (
    <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New schedule</NewButton>}>
        {(dialog) => <EditScheduleModal {...dialog} />}
    </Dialog.Trigger>
);

const ScheduleContainer = () => {
    const server = useCurrentServer()!;
    const id = server.attributes.identifier;
    const uuid = server.attributes.uuid;
    const [canCreate] = usePermissions('schedule.create');

    const { data: schedulesResponse, error, isLoading, refetch } = useServerSchedules(uuid);
    const schedules = useMemo(() => schedulesResponse?.data ?? [], [schedulesResponse?.data]);
    const columns = useMemo(() => scheduleColumns(id), [id]);
    const table = useReactTable({
        data: schedules,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (schedule) => String(schedule.attributes.id),
    });

    if (error && !schedulesResponse) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <ServerContentBlock title={'Schedules'}>
            {!schedules.length && isLoading ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    {canCreate && (
                        <ListToolbar>
                            <CreateScheduleButton />
                        </ListToolbar>
                    )}
                    <DataTable
                        table={table}
                        emptyState={
                            <Empty className={emptyCompactClass}>
                                <EmptyHeader>
                                    <EmptyMedia variant={'icon'}>
                                        <CalendarClock />
                                    </EmptyMedia>
                                    <EmptyTitle>No schedules</EmptyTitle>
                                    <EmptyDescription>
                                        Schedules run commands, power actions and backups automatically.
                                    </EmptyDescription>
                                </EmptyHeader>
                                {canCreate && (
                                    <EmptyContent>
                                        <CreateScheduleButton />
                                    </EmptyContent>
                                )}
                            </Empty>
                        }
                    />
                </>
            )}
        </ServerContentBlock>
    );
};

export default ScheduleContainer;
