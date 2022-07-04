import tw from 'twin.macro';
import * as Icon from 'react-feather';
import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import Can from '@/components/elements/Can';
import { Subuser } from '@/state/server/subusers';
import GreyRowBox from '@/components/elements/GreyRowBox';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';
import RemoveSubuserButton from '@/components/server/users/RemoveSubuserButton';

interface Props {
    subuser: Subuser;
}

export default ({ subuser }: Props) => {
    const uuid = useStoreState((state) => state.user!.data!.uuid);
    const [visible, setVisible] = useState(false);

    return (
        <GreyRowBox css={tw`mb-2`}>
            <EditSubuserModal subuser={subuser} visible={visible} onModalDismissed={() => setVisible(false)} />
            <div css={tw`w-10 h-10 rounded-full bg-white border-2 border-neutral-800 overflow-hidden hidden md:block`}>
                <img css={tw`w-full h-full`} src={`${subuser.image}?s=400`} />
            </div>
            <div css={tw`ml-4 flex-1 overflow-hidden`}>
                <p css={tw`text-sm truncate`}>{subuser.email}</p>
            </div>
            <div css={tw`ml-4`}>
                <p css={tw`font-medium text-center`}>
                    &nbsp;
                    {subuser.twoFactorEnabled ? (
                        <Icon.Lock css={!subuser.twoFactorEnabled ? tw`text-red-400` : undefined} />
                    ) : (
                        <Icon.Unlock css={!subuser.twoFactorEnabled ? tw`text-red-400` : undefined} />
                    )}
                    &nbsp;
                </p>
                <p css={tw`text-2xs text-neutral-500 uppercase hidden md:block`}>2FA Enabled</p>
            </div>
            <div css={tw`ml-4 hidden md:block`}>
                <p css={tw`font-medium text-center`}>
                    {subuser.permissions.filter((permission) => permission !== 'websocket.connect').length}
                </p>
                <p css={tw`text-2xs text-neutral-500 uppercase`}>Permissions</p>
            </div>
            {subuser.uuid !== uuid && (
                <>
                    <Can action={'user.update'}>
                        <button
                            type={'button'}
                            aria-label={'Edit subuser'}
                            css={tw`block text-sm p-1 md:p-2 text-neutral-500 hover:text-neutral-100 transition-colors duration-150 mx-4`}
                            onClick={() => setVisible(true)}
                        >
                            <Icon.PenTool />
                        </button>
                    </Can>
                    <Can action={'user.delete'}>
                        <RemoveSubuserButton subuser={subuser} />
                    </Can>
                </>
            )}
        </GreyRowBox>
    );
};
