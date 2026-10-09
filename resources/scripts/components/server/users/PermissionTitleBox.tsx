import React from 'react';
import { useStore } from '@tanstack/react-form';
import type { AnyFormApi } from '@tanstack/react-form';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Checkbox from '@/components/ui/Checkbox';
import { cn } from '@/lib/cn';
import { setPermissionsSelected } from '@/components/server/users/permissionSelection';

interface Props {
    form: AnyFormApi;
    isEditable: boolean;
    title: string;
    editablePermissions: string[];
    className?: string;
    children?: React.ReactNode;
}

const PermissionTitleBox = ({ form, isEditable, title, editablePermissions, className, children }: Props) => {
    const value = useStore(form.store, (state) => (state.values.permissions ?? []) as string[]);
    const valueSet = new Set(value);
    const canToggleGroup = isEditable && editablePermissions.length > 0;
    const allChecked = editablePermissions.length > 0 && editablePermissions.every((p) => valueSet.has(p));

    const onCheckedChange = (checked: boolean) => {
        form.setFieldValue('permissions', setPermissionsSelected(value, editablePermissions, checked));
    };

    return (
        <TitledGreyBox
            title={
                <div className='flex items-center'>
                    <p className='text-sm uppercase flex-1'>{title}</p>
                    {canToggleGroup && (
                        <Checkbox
                            aria-label={`Select all ${title} permissions`}
                            checked={allChecked}
                            indeterminate={!allChecked && editablePermissions.some((p) => valueSet.has(p))}
                            onChange={onCheckedChange}
                            className='w-5 h-5'
                        />
                    )}
                </div>
            }
            className={cn('border border-border ring-1 ring-border/60 shadow-lg', className)}
        >
            {children}
        </TitledGreyBox>
    );
};

export default PermissionTitleBox;
