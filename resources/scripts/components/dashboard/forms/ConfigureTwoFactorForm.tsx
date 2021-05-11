import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';

const ConfigureTwoFactorForm = ({ t }: WithTranslation) => {
    const user = useStoreState((state: ApplicationStore) => state.user.data!);
    const [ visible, setVisible ] = useState(false);

    return user.useTotp ?
        <div>
            {visible &&
            <DisableTwoFactorModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p css={tw`text-sm`}>
                {t('2fa_currently_enabled')}
            </p>
            <div css={tw`mt-6`}>
                <Button
                    color={'red'}
                    isSecondary
                    onClick={() => setVisible(true)}
                >
                    {t('disable')}
                </Button>
            </div>
        </div>
        :
        <div>
            {visible &&
            <SetupTwoFactorModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p css={tw`text-sm`}>
                {t('2fa_currently_disabled')}
            </p>
            <div css={tw`mt-6`}>
                <Button
                    color={'green'}
                    isSecondary
                    onClick={() => setVisible(true)}
                >
                    {t('begin_setup')}
                </Button>
            </div>
        </div>
    ;
};

export default withTranslation('dashboard')(ConfigureTwoFactorForm);
