import React from 'react';
import { useFormikContext } from 'formik';
import Input from '@/components/elements/Input';

interface Values {
    permissions: string[];
}

type Props = {
    editablePermissions: string[];
};

const AllPermissionsButton: React.FC<Props> = ({ editablePermissions }) => {
    const { values, setFieldValue } = useFormikContext<Values>();

    const allSelected =
        editablePermissions.length > 0 &&
        editablePermissions.every((p) =>
            values.permissions.includes(p)
        );

    const toggleAll = (checked: boolean) => {
        setFieldValue(
            'permissions',
            checked ? editablePermissions : []
        );
    };

    return (
        <Input
            type="checkbox"
            checked={allSelected}
            onChange={(e) => toggleAll(e.target.checked)}
        />
    );
};

export default AllPermissionsButton;