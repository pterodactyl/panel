import { useMemo } from 'react';
import { LockOpen, UserLock, Users } from 'lucide-react';
import {
    actionsColumn,
    DeleteAction,
    Dialog,
    EditAction,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Icon,
    ListToolbar,
    NewLinkButton,
    RowActions,
    SpinnerOverlay,
    Table,
    useTranslation,
    type SubuserListModel,
    type SubuserSummary,
    type TableColumn,
    type Translation,
} from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

function NewUserButton({ model }: { model: SubuserListModel }) {
    const { t } = useTranslation();

    return <NewLinkButton to={`/server/${model.serverId}/users/new`}>{t('New user')}</NewLinkButton>;
}

function SubuserActions({ model, subuser }: { model: SubuserListModel; subuser: SubuserSummary }) {
    const { t } = useTranslation();

    if (subuser.self) {
        return null;
    }

    return (
        <RowActions>
            {model.canUpdate && (
                <Dialog.Trigger
                    trigger={({ onClick }) => (
                        <EditAction aria-label={t('Edit {{name}}', { name: subuser.email })} onClick={onClick} />
                    )}
                >
                    {({ open, onClose }) =>
                        open && <View name='server.subuserForm' subuser={subuser} onClose={onClose} />
                    }
                </Dialog.Trigger>
            )}
            {model.canDelete && (
                <Dialog.ConfirmTrigger
                    title={t('Delete this subuser?')}
                    confirm={t('Yes, remove subuser')}
                    preventExternalClose={model.deleting}
                    pending={model.deleting}
                    onConfirmed={(_event, close) => {
                        void model.remove(subuser.uuid).then(close);
                    }}
                    trigger={({ onClick }) => (
                        <DeleteAction aria-label={t('Delete {{name}}', { name: subuser.email })} onClick={onClick} />
                    )}
                >
                    <SpinnerOverlay visible={model.deleting} />
                    {t(
                        'Are you sure you wish to remove this subuser? They will have all access to this server revoked immediately.'
                    )}
                </Dialog.ConfirmTrigger>
            )}
        </RowActions>
    );
}

function columns(model: SubuserListModel, t: Translation['t']): TableColumn<SubuserSummary>[] {
    return [
        {
            id: 'email',
            label: t('User'),
            sortValue: (subuser) => subuser.email,
            cell: (subuser) => (
                <div className='flex min-w-44 items-center gap-3'>
                    <img
                        className='hidden h-8 w-8 rounded-full border border-border md:block'
                        src={`${subuser.image}?s=96`}
                        alt=''
                    />
                    <span className='truncate font-medium'>{subuser.email}</span>
                </div>
            ),
        },
        {
            id: '2fa_enabled',
            label: t('2FA'),
            sortValue: (subuser) => subuser.twoFactor,
            cell: (subuser) => (
                <span className='inline-flex items-center gap-2 text-xs'>
                    <Icon
                        icon={subuser.twoFactor ? UserLock : LockOpen}
                        className={subuser.twoFactor ? 'text-success' : 'text-destructive'}
                    />
                    {subuser.twoFactor ? t('Enabled') : t('Disabled')}
                </span>
            ),
            headerClassName: 'hidden sm:table-cell w-28',
            cellClassName: 'hidden sm:table-cell w-28',
        },
        {
            id: 'permissions',
            label: t('Permissions'),
            sortValue: (subuser) => subuser.permissionCount,
            cell: (subuser) => String(subuser.permissionCount),
            headerClassName: 'hidden md:table-cell w-32',
            cellClassName: 'hidden md:table-cell w-32',
        },
        actionsColumn(2, (subuser) => <SubuserActions model={model} subuser={subuser} />),
    ];
}

export default function SubuserList({ model, className }: ViewProps<'server.subusers'>) {
    const { t } = useTranslation();
    const tableColumns = useMemo(() => columns(model, t), [model, t]);

    return (
        <div className={className}>
            {model.canCreate && (
                <ListToolbar>
                    <NewUserButton model={model} />
                </ListToolbar>
            )}
            <Table
                rows={model.subusers}
                columns={tableColumns}
                keyOf={(subuser) => subuser.uuid}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <Users />
                            </EmptyMedia>
                            <EmptyTitle>{t('No subusers')}</EmptyTitle>
                            <EmptyDescription>
                                {t('Add users to give them access to this server with the permissions you choose.')}
                            </EmptyDescription>
                        </EmptyHeader>
                        {model.canCreate && (
                            <EmptyContent>
                                <NewUserButton model={model} />
                            </EmptyContent>
                        )}
                    </Empty>
                }
            />
        </div>
    );
}
