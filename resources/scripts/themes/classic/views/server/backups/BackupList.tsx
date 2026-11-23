import { useMemo } from 'react';
import { Archive, ArrowLeft, Lock } from 'lucide-react';
import {
    actionsColumn,
    Badge,
    bytesToString,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    formatDate,
    fromNow,
    Icon,
    ListToolbar,
    NewButton,
    Spinner,
    SpinnerOverlay,
    Table,
    useTranslation,
    type BackupListModel,
    type BackupSummary,
    type TableColumn,
    type Translation,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import BackupActions from './BackupActions';
import CreateBackupButton from './CreateBackupButton';

const backupUsage = (model: BackupListModel, t: Translation['t']): string | null => {
    const { limit, count } = model;

    if (limit === 0 && model.backups.length > 0) {
        return t('Backups cannot be created for this server because the backup limit is set to 0.');
    }

    if (model.permissions.create && limit > 0 && count > 0) {
        return t('{{created}} of {{count}} backups have been created for this server.', {
            created: count,
            count: limit,
        });
    }

    return null;
};

const canAddBackup = (model: BackupListModel): boolean => model.permissions.create && model.limit > model.count;

function BackupIdentity({ backup }: { backup: BackupSummary }) {
    const { t } = useTranslation();

    return (
        <div className='flex min-w-44 items-center gap-3'>
            {backup.completedAt === null ? (
                <Spinner size='small' />
            ) : (
                <Icon
                    icon={backup.locked ? Lock : Archive}
                    className={backup.locked ? 'text-warning' : 'text-muted-foreground'}
                />
            )}
            <div className='min-w-0'>
                <div className='flex items-center gap-2'>
                    {backup.completedAt !== null && !backup.successful ? (
                        <Badge tone='danger' size='sm'>
                            {t('Failed')}
                        </Badge>
                    ) : null}
                    <p className='truncate font-medium'>{backup.name}</p>
                </div>
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground'>{backup.checksum}</p>
            </div>
        </div>
    );
}

function columns(model: BackupListModel, t: Translation['t']): TableColumn<BackupSummary>[] {
    const { permissions } = model;
    const hasActions = permissions.download || permissions.restore || permissions.delete;

    return [
        { id: 'name', label: t('Backup'), cell: (backup) => <BackupIdentity backup={backup} /> },
        {
            id: 'bytes',
            label: t('Size'),
            cell: (backup) => (
                <span className='whitespace-nowrap text-xs'>
                    {backup.completedAt && backup.successful ? bytesToString(backup.bytes) : '-'}
                </span>
            ),
            headerClassName: 'hidden md:table-cell w-28',
            cellClassName: 'hidden md:table-cell w-28',
        },
        {
            id: 'created_at',
            label: t('Created'),
            cell: (backup) => (
                <time
                    dateTime={backup.createdAt}
                    title={formatDate(backup.createdAt, 'ddd, LL LTS')}
                    className='whitespace-nowrap text-xs text-muted-foreground'
                >
                    {fromNow(backup.createdAt)}
                </time>
            ),
            headerClassName: 'hidden sm:table-cell w-32',
            cellClassName: 'hidden sm:table-cell w-32',
        },
        actionsColumn(2, (backup) =>
            hasActions && backup.completedAt ? <BackupActions model={model} backup={backup} /> : null
        ),
    ];
}

function EmptyBackups({ model }: { model: BackupListModel }) {
    const { t } = useTranslation();

    if (model.pagination.page > 1) {
        return (
            <Empty size='compact'>
                <EmptyHeader>
                    <EmptyMedia variant='icon'>
                        <Archive />
                    </EmptyMedia>
                    <EmptyTitle>{t('No backups on this page')}</EmptyTitle>
                    <EmptyDescription>{t('This page is past the end of the backup list.')}</EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <NewButton isSecondary icon={ArrowLeft} onClick={() => model.actions.setPage(1)}>
                        {t('First page')}
                    </NewButton>
                </EmptyContent>
            </Empty>
        );
    }

    return (
        <Empty size='compact'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Archive />
                </EmptyMedia>
                <EmptyTitle>{t('No backups')}</EmptyTitle>
                <EmptyDescription>
                    {model.limit > 0
                        ? t("This server doesn't have any backups yet.")
                        : t("Backups can't be created because this server's backup limit is 0.")}
                </EmptyDescription>
            </EmptyHeader>
            {canAddBackup(model) && (
                <EmptyContent>
                    <CreateBackupButton model={model} />
                </EmptyContent>
            )}
        </Empty>
    );
}

export default function BackupList({ model, className }: ViewProps<'server.backups'>) {
    const { t } = useTranslation();
    const tableColumns = useMemo(() => columns(model, t), [model, t]);
    const usage = backupUsage(model, t);
    const canAdd = canAddBackup(model);
    const { pending } = model;

    return (
        <div className={className}>
            {(usage || canAdd) && (
                <ListToolbar summary={usage}>{canAdd && <CreateBackupButton model={model} />}</ListToolbar>
            )}
            <SpinnerOverlay visible={pending.download || pending.remove || pending.restore || pending.lock} fixed />
            <Table
                rows={model.backups}
                columns={tableColumns}
                keyOf={(backup) => backup.uuid}
                emptyState={<EmptyBackups model={model} />}
                pagination={{
                    ...model.pagination,
                    summary: ({ start, end, count }) =>
                        t(
                            'Showing <value>{{start}}</value> to <value>{{end}}</value> of <value>{{count}}</value> backups',
                            {
                                start,
                                end,
                                count,
                            }
                        ),
                    onPageSelect: model.actions.setPage,
                }}
            />
        </div>
    );
}
