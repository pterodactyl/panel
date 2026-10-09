import type { ColumnDef } from '@tanstack/react-table';
import { LockOpen, UserLock } from 'lucide-react';
import { useCurrentUser } from '@/api/account/queries';
import type { Subuser } from '@/api/server/users/queries';
import Can from '@/components/elements/Can';
import Icon from '@/components/elements/Icon';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, EditAction, RowActions } from '@/components/elements/table/RowActions';
import { Dialog } from '@/components/elements/dialog';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';
import RemoveSubuserButton from '@/components/server/users/RemoveSubuserButton';

const SubuserActionsCell = ({ subuser }: { subuser: Subuser }) => {
    const currentUserUuid = useCurrentUser().uuid;

    if (subuser.attributes.uuid === currentUserUuid) {
        return null;
    }

    return (
        <RowActions>
            <Can action='user.update'>
                <Dialog.Trigger
                    trigger={({ onClick }) => (
                        <EditAction aria-label={`Edit ${subuser.attributes.email}`} onClick={onClick} />
                    )}
                >
                    {({ open, onClose }) => open && <EditSubuserModal subuser={subuser} open onClose={onClose} />}
                </Dialog.Trigger>
            </Can>
            <Can action='user.delete'>
                <RemoveSubuserButton subuser={subuser} />
            </Can>
        </RowActions>
    );
};

export const subuserColumns: ColumnDef<Subuser>[] = [
    {
        id: 'email',
        accessorFn: (subuser) => subuser.attributes.email,
        header: ({ column }) => <DataTableColumnHeader column={column} title='User' />,
        cell: ({ row }) => (
            <div className='flex min-w-44 items-center gap-3'>
                <img
                    className='hidden h-8 w-8 rounded-full border border-border md:block'
                    src={`${row.original.attributes.image}?s=96`}
                    alt=''
                />
                <span className='truncate font-medium'>{row.original.attributes.email}</span>
            </div>
        ),
    },
    {
        id: '2fa_enabled',
        accessorFn: (subuser) => subuser.attributes['2fa_enabled'],
        header: ({ column }) => <DataTableColumnHeader column={column} title='2FA' />,
        cell: ({ row }) => (
            <span className='inline-flex items-center gap-2 text-xs'>
                <Icon
                    icon={row.original.attributes['2fa_enabled'] ? UserLock : LockOpen}
                    className={row.original.attributes['2fa_enabled'] ? 'text-success' : 'text-destructive'}
                />
                {row.original.attributes['2fa_enabled'] ? 'Enabled' : 'Disabled'}
            </span>
        ),
        meta: { headerClassName: 'hidden sm:table-cell w-28', cellClassName: 'hidden sm:table-cell w-28' },
    },
    {
        id: 'permissions',
        accessorFn: (subuser) =>
            subuser.attributes.permissions.filter((permission) => permission !== 'websocket.connect').length,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Permissions' />,
        meta: { headerClassName: 'hidden md:table-cell w-32', cellClassName: 'hidden md:table-cell w-32' },
    },
    actionsColumn<Subuser>(2, (subuser) => <SubuserActionsCell subuser={subuser} />),
];
