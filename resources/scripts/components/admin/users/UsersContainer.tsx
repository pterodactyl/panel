import React, { Fragment, useCallback, useEffect, useState } from 'react';
import { QueryBuilderParams } from '@/api/http';
import { UUID } from '@/api/definitions';
import { User } from '@definitions/admin';
import { Transition } from '@/components/elements/transitions';
import { LockOpenIcon, PlusIcon, SupportIcon, TrashIcon } from '@heroicons/react/solid';
import { Button } from '@/components/elements/button/index';
import { Checkbox, InputField } from '@/components/elements/inputs';
import UserTableRow from '@/components/admin/users/UserTableRow';
import debounce from 'debounce';
import { useGetUsers } from '@/api/admin/users';
import TFootPaginated from '@/components/elements/table/TFootPaginated';

const filters = [ 'id', 'uuid', 'external_id', 'username', 'email' ] as const;
type Filters = typeof filters[number];

const extractFiltersFromString = (str: string, params: (keyof Filters)[]): QueryBuilderParams => {
    const filters: Partial<Record<string, string[]>> = {};

    const parts = str.split(' ');
    for (const segment of parts) {
        const [ filter, value ] = segment.split(':', 2);
        // @ts-ignore
        if (!filter || !value || !params.includes(filter)) {
            continue;
        }

        const key = filter as string;
        filters[key] = [ ...(filters[key] || []), value ];
    }

    if (!Object.keys(filters).length) {
        return { filters: { email: str } };
    }

    // @ts-ignore
    return { filters };
};

const UsersContainer = () => {
    const [ search, setSearch ] = useState('');
    const [ selected, setSelected ] = useState<UUID[]>([]);
    const { data: users } = useGetUsers(extractFiltersFromString(search, filters as unknown as (keyof Filters)[]));

    useEffect(() => {
        document.title = 'Admin | Users';
    }, []);

    const onRowChange = (user: User, checked: boolean) => {
        setSelected((state) => {
            return checked ? [ ...state, user.uuid ] : selected.filter((uuid) => uuid !== user.uuid);
        });
    };

    const selectAllChecked = users && users.items.length > 0 && selected.length > 0;
    const onSelectAll = () => setSelected((state) => state.length > 0 ? [] : users?.items.map(({ uuid }) => uuid) || []);

    const setSearchTerm = useCallback(debounce((term: string) => {
        setSearch(term);
    }, 200), []);

    return (
        <div>
            <div className={'flex justify-end mb-4'}>
                <Button className={'shadow focus:ring-offset-2 focus:ring-offset-neutral-800'}>
                    Add User <PlusIcon className={'ml-2 w-5 h-5'}/>
                </Button>
            </div>
            <div className={'relative flex items-center rounded-t bg-neutral-700 px-4 py-2'}>
                <div className={'mr-6'}>
                    <Checkbox
                        checked={selectAllChecked}
                        indeterminate={selected.length !== users?.items.length}
                        onChange={onSelectAll}
                    />
                </div>
                <div className={'flex-1'}>
                    <InputField
                        type={'text'}
                        name={'filter'}
                        placeholder={'Begin typing to filter...'}
                        className={'w-56 focus:w-96'}
                        onChange={e => setSearchTerm(e.currentTarget.value)}
                    />
                </div>
                <Transition.Fade as={Fragment} show={selected.length > 0} duration={'duration-75'}>
                    <div className={'absolute rounded-t bg-neutral-700 w-full h-full top-0 left-0 flex items-center justify-end space-x-4 px-4'}>
                        <div className={'flex-1'}>
                            <Checkbox
                                checked={selectAllChecked}
                                indeterminate={selected.length !== users?.items.length}
                                onChange={onSelectAll}
                            />
                        </div>
                        <Button.Text square>
                            <SupportIcon className={'w-4 h-4'}/>
                        </Button.Text>
                        <Button.Text square>
                            <LockOpenIcon className={'w-4 h-4'}/>
                        </Button.Text>
                        <Button.Text square>
                            <TrashIcon className={'w-4 h-4'}/>
                        </Button.Text>
                    </div>
                </Transition.Fade>
            </div>
            <table className={'min-w-full rounded bg-neutral-700'}>
                <thead className={'bg-neutral-900'}>
                    <tr>
                        <th scope={'col'} className={'w-8'}/>
                        <th scope={'col'} className={'text-left px-6 py-2 w-full'}>Email</th>
                        <th scope={'col'}/>
                        <th scope={'col'}/>
                    </tr>
                </thead>
                <tbody>
                    {users?.items.map(user => (
                        <UserTableRow
                            key={user.uuid}
                            user={user}
                            selected={selected.includes(user.uuid)}
                            onRowChange={onRowChange}
                        />
                    ))}
                </tbody>
                {users && <TFootPaginated span={4} pagination={users.pagination}/>}
            </table>
        </div>
    );
};

export default UsersContainer;
