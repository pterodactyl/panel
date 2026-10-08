import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { Network } from 'lucide-react';
import Spinner from '@/components/elements/Spinner';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewButton } from '@/components/elements/NewButton';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCurrentServer } from '@/api/server/queries';
import {
    createServerAllocationInput,
    useCreateServerAllocation,
    useServerAllocations,
} from '@/api/server/network/queries';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { allocationColumns } from '@/components/server/network/AllocationTable';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { usePermissions } from '@/plugins/usePermissions';

const NetworkContainer = () => {
    const server = useCurrentServer()!;
    const uuid = server.attributes.uuid;
    const allocationLimit = server.attributes.feature_limits.allocations ?? 0;
    const [canCreate] = usePermissions('allocation.create');

    const { data, error, refetch } = useServerAllocations(uuid);
    const createAllocation = useCreateServerAllocation();
    const allocations = useMemo(() => data?.data ?? [], [data?.data]);
    const table = useReactTable({
        data: allocations,
        columns: allocationColumns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (allocation) => String(allocation.attributes.id),
    });

    const onCreateAllocation = () => {
        createAllocation.mutate(createServerAllocationInput(uuid));
    };

    if (error && !data) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    const canManage = canCreate && allocationLimit > 0;
    const canAddAllocation = canManage && allocationLimit > allocations.length;
    const newAllocationButton = <NewButton onClick={onCreateAllocation}>New allocation</NewButton>;

    return (
        <ServerContentBlock title='Network'>
            {data ? (
                <>
                    <SpinnerOverlay visible={createAllocation.isPending} />
                    {canManage && (
                        <ListToolbar
                            summary={`You are currently using ${allocations.length} of ${allocationLimit} allowed allocations for this server.`}
                        >
                            {canAddAllocation && newAllocationButton}
                        </ListToolbar>
                    )}
                    <DataTable
                        table={table}
                        emptyState={
                            <Empty className={emptyCompactClass}>
                                <EmptyHeader>
                                    <EmptyMedia variant='icon'>
                                        <Network />
                                    </EmptyMedia>
                                    <EmptyTitle>No allocations</EmptyTitle>
                                    <EmptyDescription>
                                        This server doesn&apos;t have any network allocations assigned.
                                    </EmptyDescription>
                                </EmptyHeader>
                                {canAddAllocation && <EmptyContent>{newAllocationButton}</EmptyContent>}
                            </Empty>
                        }
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </ServerContentBlock>
    );
};

export default NetworkContainer;
