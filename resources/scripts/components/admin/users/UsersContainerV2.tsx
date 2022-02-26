import React, { useEffect, useState } from 'react';
import http from '@/api/http';
import { User } from '@/api/admin/user';
import { AdminTransformers } from '@/api/admin/transformers';
import { Dropdown } from '@/components/elements/dropdown';
import { DotsVerticalIcon, LockClosedIcon, PaperAirplaneIcon, PencilIcon, TrashIcon } from '@heroicons/react/solid';

const UsersContainerV2 = () => {
    const [ users, setUsers ] = useState<User[]>([]);
    useEffect(() => {
        document.title = 'Admin | Users';
    }, []);

    useEffect(() => {
        http.get('/api/application/users')
            .then(({ data }) => {
                setUsers(data.data.map(AdminTransformers.toUser));
            })
            .catch(console.error);
    }, []);

    return (
        <div className={'bg-neutral-700'}>
            <table className={'min-w-full rounded'}>
                <thead className={'bg-neutral-900'}>
                    <tr>
                        <th scope={'col'} className={'w-8'}/>
                        <th scope={'col'} className={'text-left px-6 py-2 w-full'}>Email</th>
                        <th scope={'col'}/>
                    </tr>
                </thead>
                <tbody>
                    {users.map(user => (
                        <tr key={user.uuid}>
                            <td className={'whitespace-nowrap'}>
                                <div className={'flex justify-end items-center w-8'}>
                                    <input type={'checkbox'}/>
                                </div>
                            </td>
                            <td className={'pl-6 py-4 whitespace-nowrap'}>
                                <div className={'flex items-center'}>
                                    <div className={'w-10 h-10'}>
                                        <img
                                            src={user.avatarUrl}
                                            className={'w-10 h-10 rounded-full'}
                                            alt={'User avatar'}
                                        />
                                    </div>
                                    <div className={'ml-4'}>
                                        <p className={'font-medium'}>
                                            {user.email}
                                        </p>
                                        <p className={'text-sm text-neutral-400'}>
                                            {user.uuid}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td className={'px-6 py-4 whitespace-nowrap'}>
                                <Dropdown>
                                    <Dropdown.Button className={'px-2'}>
                                        <DotsVerticalIcon />
                                    </Dropdown.Button>
                                    <Dropdown.Item icon={<PencilIcon />}>Edit</Dropdown.Item>
                                    <Dropdown.Item icon={<PaperAirplaneIcon />}>Reset Password</Dropdown.Item>
                                    <Dropdown.Item icon={<LockClosedIcon />}>Suspend</Dropdown.Item>
                                    <Dropdown.Gap />
                                    <Dropdown.Item icon={<TrashIcon />} danger>Delete Account</Dropdown.Item>
                                </Dropdown>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default UsersContainerV2;
