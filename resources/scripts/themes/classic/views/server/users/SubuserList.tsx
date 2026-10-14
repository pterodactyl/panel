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
    type SubuserListModel,
    type SubuserSummary,
    type TableColumn,
} from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

const NewUserButton = ({ model }: { model: SubuserListModel }) => (
    <NewLinkButton to={`/server/${model.serverId}/users/new`}>New user</NewLinkButton>
);

function SubuserActions({ model, subuser }: { model: SubuserListModel; subuser: SubuserSummary }) {
    if (subuser.self) {
        return null;
    }

    return (
        <RowActions>
            {model.canUpdate && (
                <Dialog.Trigger
                    trigger={({ onClick }) => <EditAction aria-label={`Edit ${subuser.email}`} onClick={onClick} />}
                >
                    {({ open, onClose }) =>
                        open && <View name='server.subuserForm' subuser={subuser} onClose={onClose} />
                    }
                </Dialog.Trigger>
            )}
            {model.canDelete && (
                <Dialog.ConfirmTrigger
                    title='Delete this subuser?'
                    confirm='Yes, remove subuser'
                    preventExternalClose={model.deleting}
                    pending={model.deleting}
                    onConfirmed={(_event, close) => {
                        void model.remove(subuser.uuid).then(close);
                    }}
                    trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${subuser.email}`} onClick={onClick} />}
                >
                    <SpinnerOverlay visible={model.deleting} />
                    Are you sure you wish to remove this subuser? They will have all access to this server revoked
                    immediately.
                </Dialog.ConfirmTrigger>
            )}
        </RowActions>
    );
}

function columns(model: SubuserListModel): TableColumn<SubuserSummary>[] {
    return [
        {
            id: 'email',
            label: 'User',
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
            label: '2FA',
            sortValue: (subuser) => subuser.twoFactor,
            cell: (subuser) => (
                <span className='inline-flex items-center gap-2 text-xs'>
                    <Icon
                        icon={subuser.twoFactor ? UserLock : LockOpen}
                        className={subuser.twoFactor ? 'text-success' : 'text-destructive'}
                    />
                    {subuser.twoFactor ? 'Enabled' : 'Disabled'}
                </span>
            ),
            headerClassName: 'hidden sm:table-cell w-28',
            cellClassName: 'hidden sm:table-cell w-28',
        },
        {
            id: 'permissions',
            label: 'Permissions',
            sortValue: (subuser) => subuser.permissionCount,
            cell: (subuser) => String(subuser.permissionCount),
            headerClassName: 'hidden md:table-cell w-32',
            cellClassName: 'hidden md:table-cell w-32',
        },
        actionsColumn(2, (subuser) => <SubuserActions model={model} subuser={subuser} />),
    ];
}

export default function SubuserList({ model, className }: ViewProps<'server.subusers'>) {
    const tableColumns = useMemo(() => columns(model), [model]);

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
                            <EmptyTitle>No subusers</EmptyTitle>
                            <EmptyDescription>
                                Add users to give them access to this server with the permissions you choose.
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
