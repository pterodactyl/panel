import React, { useState } from 'react';
import { useFormikContext } from 'formik';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';
import { User, searchUserAccounts } from '@/api/admin/user';

export default ({ selected }: { selected: User }) => {
    const context = useFormikContext();

    const [ user, setUser ] = useState<User | null>(selected);
    const [ users, setUsers ] = useState<User[] | null>(null);

    const onSearch = async (query: string) => {
        setUsers(
            await searchUserAccounts({ filters: { username: query, email: query } })
        );
    };

    const onSelect = (user: User | null) => {
        setUser(user);
        context.setFieldValue('ownerId', user?.id || null);
    };

    const getSelectedText = (user: User | null): string => user?.email || '';

    return (
        <SearchableSelect
            id={'ownerId'}
            name={'ownerId'}
            label={'Owner'}
            placeholder={'Select a user...'}
            items={users}
            selected={user}
            setSelected={setUser}
            setItems={setUsers}
            onSearch={onSearch}
            onSelect={onSelect}
            getSelectedText={getSelectedText}
            nullable
        >
            {users?.map(d => (
                <Option key={d.id} selectId={'ownerId'} id={d.id} item={d} active={d.id === user?.id}>
                    {d.email}
                </Option>
            ))}
        </SearchableSelect>
    );
};
