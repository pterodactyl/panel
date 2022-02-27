import React, { Fragment, useEffect, useState } from 'react';
import http from '@/api/http';
import { UUID } from '@/api/definitions';
import { Transformers, User } from '@definitions/admin';
import { Transition } from '@/components/elements/transitions';
import { LockOpenIcon, PlusIcon, SupportIcon, TrashIcon } from '@heroicons/react/solid';
import { Button } from '@/components/elements/button/index';
import { Checkbox, InputField } from '@/components/elements/inputs';
import UserTableRow from '@/components/admin/users/UserTableRow';

const UsersContainerV2 = () => {
    const [ users, setUsers ] = useState<User[]>([]);
    const [ selected, setSelected ] = useState<UUID[]>([]);

    useEffect(() => {
        document.title = 'Admin | Users';
    }, []);

    useEffect(() => {
        http.get('/api/application/users')
            .then(({ data }) => {
                setUsers(data.data.map(Transformers.toUser));
            })
            .catch(console.error);
    }, []);

    const onRowChange = (user: User, checked: boolean) => {
        setSelected((state) => {
            return checked ? [ ...state, user.uuid ] : selected.filter((uuid) => uuid !== user.uuid);
        });
    };

    const selectAllChecked = users.length > 0 && selected.length > 0;
    const onSelectAll = () => setSelected((state) => state.length > 0 ? [] : users.map(({ uuid }) => uuid));

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
                        indeterminate={selected.length !== users.length}
                        onChange={onSelectAll}
                    />
                </div>
                <div className={'flex-1'}>
                    <InputField
                        type={'text'}
                        name={'filter'}
                        placeholder={'Begin typing to filter...'}
                        className={'w-56 focus:w-96'}
                    />
                </div>
                <Transition.Fade as={Fragment} show={selected.length > 0} duration={'duration-75'}>
                    <div className={'absolute rounded-t bg-neutral-700 w-full h-full top-0 left-0 flex items-center justify-end space-x-4 px-4'}>
                        <div className={'flex-1'}>
                            <Checkbox
                                checked={selectAllChecked}
                                indeterminate={selected.length !== users.length}
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
                    {users.map(user => (
                        <UserTableRow
                            key={user.uuid}
                            user={user}
                            selected={selected.includes(user.uuid)}
                            onRowChange={onRowChange}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default UsersContainerV2;
