import { Checkbox } from '@/components/elements/inputs';
import { Dropdown } from '@/components/elements/dropdown';
import { BanIcon, DotsVerticalIcon, LockOpenIcon, PencilIcon, SupportIcon, TrashIcon } from '@heroicons/react/solid';
import React, { useState } from 'react';
import { User } from '@definitions/admin';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';

interface Props {
    user: User;
    selected?: boolean;
    onRowChange: (user: User, selected: boolean) => void;
}

const UserTableRow = ({ user, selected, onRowChange }: Props) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
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
            <tr>
                <td className={'whitespace-nowrap'}>
                    <div className={'flex justify-end items-center w-8'}>
                        <Checkbox checked={selected} onChange={e => onRowChange(user, e.currentTarget.checked)}/>
                    </div>
                </td>
                <td className={'pl-6 py-4 whitespace-nowrap'}>
                    <div className={'flex items-center'}>
                        <div className={'w-10 h-10'}>
                            <img src={user.avatarUrl} className={'w-10 h-10 rounded-full'} alt={'User avatar'}/>
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
                    <span className={'bg-green-100 uppercase text-green-700 font-semibold text-xs px-2 py-0.5 rounded'}>
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
        </>
    );
};

export default UserTableRow;
