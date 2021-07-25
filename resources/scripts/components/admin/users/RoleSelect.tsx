import React, { useState } from 'react';
import { useFormikContext } from 'formik';
import { Role } from '@/api/admin/roles/getRoles';
import searchRoles from '@/api/admin/roles/searchRoles';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';

export default ({ selected }: { selected: Role | null }) => {
    const context = useFormikContext();

    const [ role, setRole ] = useState<Role | null>(selected);
    const [ roles, setRoles ] = useState<Role[] | null>(null);

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

    const onSelect = (role: Role | null) => {
        setRole(role);
        context.setFieldValue('adminRoleId', role?.id || null);
    };

    const getSelectedText = (role: Role | null): string | undefined => {
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
