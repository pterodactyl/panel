import React, { useState } from 'react';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';
import { useStoreState } from '@/state/hooks';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';

const ConfigureTwoFactorForm = ({ t }: WithTranslation) => {
    const isUsingTOTP = useStoreState(state => state.user.data!.useTotp);
    const [ visible, setVisible ] = useState(false);

    return (
        <div>
            {visible &&
            <>
                {isUsingTOTP ?
                    <DisableTwoFactorModal appear visible onDismissed={() => setVisible(false)}/>
                    :
                    <SetupTwoFactorModal appear visible onDismissed={() => setVisible(false)}/>
                }
            </>
            }
            <p css={tw`text-sm`}>
                {t(isUsingTOTP ? 'dashboard:2fa.dashboard_desc_enabled' : 'dashboard:2fa.dashboard_desc_disabled')}
            </p>
            <div css={tw`mt-6`}>
                <Button
                    isSecondary
                    color={isUsingTOTP ? 'red' : 'green'}
                    onClick={() => setVisible(true)}
                >
                    {t(isUsingTOTP ? 'elements:disable' : 'elements:enable')}
                </Button>
            </div>
        </div>
    );
};

export default withTranslation([ 'elements', 'dashboard' ])(ConfigureTwoFactorForm);
