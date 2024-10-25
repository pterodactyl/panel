import { useFormikContext } from 'formik';
import { useState } from 'react';

import { searchRoles } from '@/api/admin/roles';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';
import type { UserRole } from '@definitions/admin';

export default ({ selected }: { selected: UserRole | null }) => {
    const context = useFormikContext();

    const [role, setRole] = useState<UserRole | null>(selected);
    const [roles, setRoles] = useState<UserRole[] | null>(null);

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve, reject) => {
            searchRoles({ name: query })
                .then(roles => {
                    setRoles(roles);
                    return resolve();
                })
                .catch(reject);
        });
    };

    const onSelect = (role: UserRole | null) => {
        setRole(role);
        context.setFieldValue('adminRoleId', role?.id || null);
    };

    const getSelectedText = (role: UserRole | null): string | undefined => {
        return role?.name;
    };

    return (
        <SearchableSelect
            id={'adminRoleId'}
            name={'adminRoleId'}
            label={'Role'}
            placeholder={'Select a role...'}
            items={roles}
            selected={role}
            setSelected={setRole}
            setItems={setRoles}
            onSearch={onSearch}
            onSelect={onSelect}
            getSelectedText={getSelectedText}
            nullable
        >
            {roles?.map(d => (
                <Option key={d.id} selectId={'adminRoleId'} id={d.id} item={d} active={d.id === role?.id}>
                    {d.name}
                </Option>
            ))}
        </SearchableSelect>
    );
};
