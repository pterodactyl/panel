import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { Users } from 'lucide-react';
import { useCurrentServer } from '@/api/server/queries';
import Spinner from '@/components/elements/Spinner';
import AddSubuserButton from '@/components/server/users/AddSubuserButton';
import ListToolbar from '@/components/elements/ListToolbar';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { usePermissions } from '@/plugins/usePermissions';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { useServerSubusers } from '@/api/server/users/queries';
import { useSystemPermissions } from '@/api/system/queries';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { subuserColumns } from '@/components/server/users/SubuserTable';

const hasPermissionGroups = <T extends object>(permissions: T | undefined): boolean => {
    if (!permissions) {
        return false;
    }

    return Object.keys(permissions).length > 0;
};

const UsersEmptyState = ({ canCreate }: { canCreate: boolean }) => (
    <Empty className={emptyCompactClass}>
        <EmptyHeader>
            <EmptyMedia variant='icon'>
                <Users />
            </EmptyMedia>
            <EmptyTitle>No subusers</EmptyTitle>
            <EmptyDescription>
                Add users to give them access to this server with the permissions you choose.
            </EmptyDescription>
        </EmptyHeader>
        {canCreate && (
            <EmptyContent>
                <AddSubuserButton />
            </EmptyContent>
        )}
    </Empty>
);

const UsersContainer = () => {
    const server = useCurrentServer();
    const uuid = server?.attributes.uuid ?? '';
    const [canCreate] = usePermissions('user.create');
    const { data: subusers, error, isLoading, refetch } = useServerSubusers(uuid);
    const subuserList = useMemo(() => subusers?.data ?? [], [subusers?.data]);

    const {
        data: permissionsResponse,
        error: permissionsError,
        isLoading: isLoadingPermissions,
        refetch: refetchPermissions,
    } = useSystemPermissions();
    const permissions = permissionsResponse?.attributes.permissions;
    const table = useReactTable({
        data: subuserList,
        columns: subuserColumns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (subuser) => subuser.attributes.uuid,
    });

    if (error && !subusers) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    if (permissionsError && !permissions) {
        return <ServerError message={httpErrorToHuman(permissionsError)} onRetry={() => refetchPermissions()} />;
    }

    if (!subuserList.length && (isLoading || isLoadingPermissions || !hasPermissionGroups(permissions))) {
        return <Spinner size='large' centered />;
    }

    return (
        <ServerContentBlock title='Users'>
            {canCreate && (
                <ListToolbar>
                    <AddSubuserButton />
                </ListToolbar>
            )}
            <DataTable table={table} emptyState={<UsersEmptyState canCreate={canCreate} />} />
        </ServerContentBlock>
    );
};

export default UsersContainer;
