import { useStore } from '@tanstack/react-form';
import type { AnyFormApi } from '@tanstack/react-form';
import Checkbox from '@/components/ui/Checkbox';
import { setPermissionsSelected } from '@/components/server/users/permissionSelection';

interface Props {
    form: AnyFormApi;
    editablePermissions: readonly string[];
    disabled?: boolean;
}

const AllPermissionsButton = ({ form, editablePermissions, disabled = false }: Props) => {
    const selectedPermissions = useStore(form.store, (state) => (state.values.permissions ?? []) as string[]);
    const selectedPermissionSet = new Set(selectedPermissions);
    const allSelected =
        editablePermissions.length > 0 &&
        editablePermissions.every((permission) => selectedPermissionSet.has(permission));
    const someSelected = editablePermissions.some((permission) => selectedPermissionSet.has(permission));

    const toggleAll = (checked: boolean) => {
        form.setFieldValue('permissions', setPermissionsSelected(selectedPermissions, editablePermissions, checked));
    };

    if (editablePermissions.length === 0) {
        return null;
    }

    return (
        <div className='flex items-center gap-2 text-sm'>
            <Checkbox
                aria-label='Select all permissions'
                checked={allSelected}
                indeterminate={!allSelected && someSelected}
                disabled={disabled}
                onChange={toggleAll}
                className='w-5 h-5'
            />
        </div>
    );
};

export default AllPermissionsButton;
