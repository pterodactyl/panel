import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';

const ConfigureTwoFactorForm = ({ t }: WithTranslation) => {
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
                {isEnabled ? t('account:2fa.dashboard_desc_enabled') : t('account:2fa.dashboard_desc_disabled')}
            </p>
            <div css={tw`mt-6`}>
                <Button color={'red'} isSecondary onClick={() => setVisible(true)}>
                    {isEnabled ? t('elements:disable') : t('elements:enable')}
                </Button>
            </div>
        </div>
    );
};

export default withTranslation([ 'elements', 'account' ])(ConfigureTwoFactorForm);
