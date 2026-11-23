import { Button, ContentBox, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import DisableTwoFactorDialog from './DisableTwoFactorDialog';
import RecoveryTokensDialog from './RecoveryTokensDialog';
import SetupTwoFactorDialog from './SetupTwoFactorDialog';

export default function TwoFactorCard({ model, className }: ViewProps<'account.twoFactor'>) {
    const { t } = useTranslation();

    return (
        <ContentBox className={className} title={t('Two-Step Verification')}>
            <div>
                <SetupTwoFactorDialog model={model} />
                <RecoveryTokensDialog tokens={model.recoveryTokens} onClose={model.dismissRecoveryTokens} />
                <DisableTwoFactorDialog model={model} />
                <p className='text-sm'>
                    {model.enabled
                        ? t('Two-step verification is currently enabled on your account.')
                        : t(
                              'You do not currently have two-step verification enabled on your account. Click the button below to begin configuring it.'
                          )}
                </p>
                <div className='mt-6'>
                    {model.enabled ? (
                        <Button.Danger onClick={() => model.openDialog('disable')}>
                            {t('Disable Two-Step')}
                        </Button.Danger>
                    ) : (
                        <Button onClick={() => model.openDialog('enable')}>{t('Enable Two-Step')}</Button>
                    )}
                </div>
            </div>
        </ContentBox>
    );
}
