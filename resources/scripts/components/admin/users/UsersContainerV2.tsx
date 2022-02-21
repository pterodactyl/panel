import React, { useEffect, useState } from 'react';
import http from '@/api/http';
import { User } from '@/api/admin/user';
import { AdminTransformers } from '@/api/admin/transformers';
import { Menu } from '@headlessui/react';
import { ChevronDownIcon, LockClosedIcon } from '@heroicons/react/solid';

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
        <div className={'overflow-hidden rounded bg-neutral-700'}>
            <table className={'min-w-full'}>
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
                                <Menu as={'div'} className={'relative inline-block text-left'}>
                                    <Menu.Button
                                        className={'inline-flex justify-center w-full px-4 py-2 font-medium text-white rounded-md'}
                                    >
                                        Options
                                        <ChevronDownIcon
                                            aria-hidden={'true'}
                                            className={'w-5 h-5 -mr-1 ml-2 text-neutral-100'}
                                        />
                                    </Menu.Button>
                                    <Menu.Items className={'absolute right-0 mt-2 origin-top-right bg-neutral-900 z-10 w-56'}>
                                        <div className={'px-1 py-1'}>
                                            <Menu.Item>
                                                {() => (
                                                    <a href={'#'} className={'group flex rounded items-center w-full px-2 py-2 hover:bg-neutral-800'}>
                                                        <LockClosedIcon className={'w-5 h-5 mr-2'} />
                                                        <span>Reset Password</span>
                                                    </a>
                                                )}
                                            </Menu.Item>
                                            <Menu.Item>
                                                {() => (
                                                    <a href={'#'} className={'group flex rounded items-center w-full px-2 py-2'}>Delete</a>
                                                )}
                                            </Menu.Item>
                                            <Menu.Item
                                                disabled
                                            >
                                                <span className={'group flex rounded items-center w-full px-2 py-2 opacity-75'}>
                                                Resend Invite
                                                </span>
                                            </Menu.Item>
                                        </div>
                                    </Menu.Items>
                                </Menu>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default UsersContainerV2;
