import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

export default () => {
    const [ visible, setVisible ] = useState(false);
    const isEnabled = useStoreState((state: ApplicationStore) => state.user.data!.useTotp);

    return (
        <div>
            {visible && (
                isEnabled ?
                    <DisableTwoFactorModal visible={visible} onModalDismissed={() => setVisible(false)}/>
                    :
                    <SetupTwoFactorModal visible={visible} onModalDismissed={() => setVisible(false)}/>
            )}
            <p css={tw`text-sm`}>
                {isEnabled ?
                    '您的帐户当前启用了双重身份验证。'
                    :
                    '您目前没有在您的帐户上启用双重身份验证， 单击下面的按钮即可开始配置。'
                }
            </p>
            <div css={tw`mt-6`}>
                <Button color={'red'} isSecondary onClick={() => setVisible(true)}>
                    {isEnabled ? '禁用' : '启用'}
                </Button>
            </div>
        </div>
    );
};
