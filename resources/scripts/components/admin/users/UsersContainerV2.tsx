import React, { useEffect, useState } from 'react';
import http from '@/api/http';
import { User } from '@/api/admin/user';
import { AdminTransformers } from '@/api/admin/transformers';
import { Dropdown } from '@/components/elements/dropdown';
import {
    BanIcon,
    DotsVerticalIcon,
    LockOpenIcon,
    PencilIcon,
    PlusIcon,
    SupportIcon,
    TrashIcon,
} from '@heroicons/react/solid';
import { Button } from '@/components/elements/button/index';
import { Dialog } from '@/components/elements/dialog';
import { Checkbox, InputField } from '@/components/elements/inputs';

const UsersContainerV2 = () => {
    const [ users, setUsers ] = useState<User[]>([]);
    useEffect(() => {
        document.title = 'Admin | Users';
    }, []);

    const [ visible, setVisible ] = useState(false);

    useEffect(() => {
        http.get('/api/application/users')
            .then(({ data }) => {
                setUsers(data.data.map(AdminTransformers.toUser));
            })
            .catch(console.error);
    }, []);

    return (
        <div>
            <div className={'flex justify-end mb-4'}>
                <Button className={'shadow focus:ring-offset-2 focus:ring-offset-neutral-800'}>
                    Add User <PlusIcon className={'ml-2 w-5 h-5'}/>
                </Button>
            </div>
            <Dialog title={'Delete account'} visible={visible} onDismissed={() => setVisible(false)}>
                <Dialog.Icon type={'danger'}/>
                This account will be permanently deleted.
                <Dialog.Buttons>
                    <Button.Text
                        onClick={() => setVisible(false)}
                    >
                        Cancel
                    </Button.Text>
                    <Button.Danger>Delete</Button.Danger>
                </Dialog.Buttons>
            </Dialog>
            <div className={'relative flex items-center rounded-t bg-neutral-700 px-4 py-2'}>
                <div className={'mr-6'}>
                    <Checkbox />
                </div>
                <div className={'flex-1'}>
                    <InputField type={'text'} name={'filter'} placeholder={'Begin typing to filter...'} className={'w-56 focus:w-96'} />
                </div>
                <div className={'absolute rounded-t bg-neutral-700 w-full h-full top-0 left-0 flex items-center justify-end space-x-4 px-4'}>
                    <div className={'flex-1'}>
                        <Checkbox indeterminate />
                    </div>
                    <Button.Text square>
                        <SupportIcon className={'w-4 h-4'} />
                    </Button.Text>
                    <Button.Text square>
                        <LockOpenIcon className={'w-4 h-4'} />
                    </Button.Text>
                    <Button.Text square>
                        <TrashIcon className={'w-4 h-4'} />
                    </Button.Text>
                </div>
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
                        <tr key={user.uuid}>
                            <td className={'whitespace-nowrap'}>
                                <div className={'flex justify-end items-center w-8'}>
                                    <Checkbox/>
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
                            <td className={'pl-2 py-4 whitespace-nowrap'}>
                                {user.isUsingTwoFactor &&
                                <span className={'bg-green-100 uppercase text-green-600 font-semibold text-xs px-2 py-0.5 rounded'}>
                                    2-FA Enabled
                                </span>
                                }
                            </td>
                            <td className={'px-6 py-4 whitespace-nowrap'}>
                                <Dropdown>
                                    <Dropdown.Button className={'px-2'}>
                                        <DotsVerticalIcon/>
                                    </Dropdown.Button>
                                    <Dropdown.Item icon={<PencilIcon/>}>Edit</Dropdown.Item>
                                    <Dropdown.Item icon={<SupportIcon/>}>Reset Password</Dropdown.Item>
                                    <Dropdown.Item icon={<LockOpenIcon/>} disabled={!user.isUsingTwoFactor}>
                                        Disable 2-FA
                                    </Dropdown.Item>
                                    <Dropdown.Item icon={<BanIcon/>}>Suspend</Dropdown.Item>
                                    <Dropdown.Gap/>
                                    <Dropdown.Item icon={<TrashIcon/>} onClick={() => setVisible(true)} danger>Delete
                                        Account
                                    </Dropdown.Item>
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
