import React, { useState } from 'react';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';
import { useStoreState } from '@/state/hooks';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';


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
                {isEnabled 'dashboard:2fa.dashboard_desc_enabled' : 'dashboard:2fa.dashboard_desc_disabled')}
            </p>
            <div css={tw`mt-6`}>
                <Button color={'red'} isSecondary onClick={() => setVisible(true)}>
                    {isEnabled ? 'elements:disable' : 'elements:enable'}
                </Button>
            </div>
        </div>
    );
};

export default withTranslation([ 'elements', 'dashboard' ])(ConfigureTwoFactorForm);
