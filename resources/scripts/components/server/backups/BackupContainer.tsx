import { useCallback, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { getCoreRowModel, useReactTable, type OnChangeFn, type PaginationState } from '@tanstack/react-table';
import { useNavigate, useParams } from '@tanstack/react-router';
import { Archive, ArrowLeft } from 'lucide-react';
import Spinner from '@/components/elements/Spinner';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewButton } from '@/components/elements/NewButton';
import CreateBackupButton from '@/components/server/backups/CreateBackupButton';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { useCurrentServer } from '@/api/server/queries';
import { updateServerBackup, useServerBackups } from '@/api/server/backups/queries';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import { getPageSearch, usePageSearch } from '@/router/search';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { backupColumns } from '@/components/server/backups/BackupTable';
import { completeBackup, parseBackupCompletedPayload } from '@/components/server/backups/backupCompletion';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { usePermissions } from '@/plugins/usePermissions';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

const backupUsage = (limit: number, count: number, hasBackups: boolean, canCreate: boolean): string | null => {
    if (limit === 0 && hasBackups) {
        return 'Backups cannot be created for this server because the backup limit is set to 0.';
    }

    if (canCreate && limit > 0 && count > 0) {
        return `${count} of ${limit} backups have been created for this server.`;
    }

    return null;
};

function PastEndEmptyState({ onFirstPage }: { onFirstPage: () => void }) {
    return (
        <Empty className={emptyCompactClass}>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Archive />
                </EmptyMedia>
                <EmptyTitle>No backups on this page</EmptyTitle>
                <EmptyDescription>This page is past the end of the backup list.</EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <NewButton isSecondary icon={ArrowLeft} onClick={onFirstPage}>
                    First page
                </NewButton>
            </EmptyContent>
        </Empty>
    );
}

type BackupsEmptyStateProps = {
    page: number;
    backupLimit: number;
    canAddBackup: boolean;
    onFirstPage: () => void;
};

function BackupsEmptyState({ page, backupLimit, canAddBackup, onFirstPage }: BackupsEmptyStateProps) {
    if (page > 1) {
        return <PastEndEmptyState onFirstPage={onFirstPage} />;
    }

    return (
        <Empty className={emptyCompactClass}>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Archive />
                </EmptyMedia>
                <EmptyTitle>No backups</EmptyTitle>
                <EmptyDescription>
                    {backupLimit > 0
                        ? "This server doesn't have any backups yet."
                        : "Backups can't be created because this server's backup limit is 0."}
                </EmptyDescription>
            </EmptyHeader>
            {canAddBackup && (
                <EmptyContent>
                    <CreateBackupButton />
                </EmptyContent>
            )}
        </Empty>
    );
}

export default function BackupContainer() {
    const navigate = useNavigate();
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const page = usePageSearch();
    const server = useCurrentServer()!;
    const [canCreate] = usePermissions('backup.create');
    const queryClient = useQueryClient();
    const { data: backups, error, refetch } = useServerBackups(server.attributes.uuid, page);
    const columns = useMemo(() => backupColumns(page), [page]);
    const data = useMemo(() => backups?.data ?? [], [backups?.data]);
    const pagination = useMemo<PaginationState>(
        () => ({ pageIndex: page - 1, pageSize: backups?.meta.pagination.per_page ?? 20 }),
        [backups?.meta.pagination.per_page, page]
    );
    const onPaginationChange = useCallback<OnChangeFn<PaginationState>>(
        (updater) => {
            const next = updater instanceof Function ? updater(pagination) : updater;

            if (next.pageIndex === pagination.pageIndex) {
                return;
            }

            void navigate({
                to: '/server/$id/backups',
                params: { id },
                search: getPageSearch(next.pageIndex + 1),
                replace: true,
                viewTransition: false,
            });
        },
        [id, navigate, pagination]
    );
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (backup) => backup.attributes.uuid,
        manualPagination: true,
        rowCount: backups?.meta.pagination.total ?? 0,
        state: { pagination },
        onPaginationChange,
    });

    useWebsocketEvent(SocketEvent.BACKUP_COMPLETED, (data) => {
        const payload = parseBackupCompletedPayload(data);

        if (!payload) {
            console.warn('Ignoring a malformed backup completion event.', data);

            return;
        }

        updateServerBackup(queryClient, server.attributes.uuid, page, payload.uuid, (backup) =>
            completeBackup(backup, payload, new Date().toISOString())
        );
    });

    const backupLimit = server.attributes.feature_limits.backups ?? 0;

    if (error && !backups) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    if (!backups) {
        return <Spinner size='large' centered />;
    }

    const backupCount = backups.meta.backup_count;
    const canAddBackup = canCreate && backupLimit > backupCount;
    const usage = backupUsage(backupLimit, backupCount, backups.data.length > 0, canCreate);

    return (
        <ServerContentBlock title='Backups'>
            {(usage || canAddBackup) && (
                <ListToolbar summary={usage}>{canAddBackup && <CreateBackupButton />}</ListToolbar>
            )}
            <DataTable
                table={table}
                emptyState={
                    <BackupsEmptyState
                        page={page}
                        backupLimit={backupLimit}
                        canAddBackup={canAddBackup}
                        onFirstPage={() => table.setPageIndex(0)}
                    />
                }
            />
            <DataTablePagination
                table={table}
                total={backups.meta.pagination.total}
                count={backups.meta.pagination.count}
                itemLabel='backups'
            />
        </ServerContentBlock>
    );
}
