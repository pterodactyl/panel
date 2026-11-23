import { Alert, cn, PageContentBlock, PageHeading, useTranslation } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';

const CARD_WIDTH = 'w-full sm:w-[calc(50%_-_1rem)] md:w-auto md:flex-1';

export default function AccountOverview({ model, className }: ViewProps<'page.account.overview'>) {
    const { t } = useTranslation();

    return (
        <PageContentBlock title={t('Account Overview')} className={className}>
            <PageHeading
                title={t('Account')}
                description={t('Manage your profile, credentials, and account security.')}
            />
            {model.twoFactorRequired && (
                <Alert title={t('2-Factor Required')} type='danger'>
                    {t('Your account must have two-factor authentication enabled in order to continue.')}
                </Alert>
            )}
            <Slot name='account.overview.before' />
            <div className={cn('flex flex-wrap lg:grid lg:grid-cols-3 mb-10', model.twoFactorRequired && 'mt-4')}>
                <View name='account.password' className={CARD_WIDTH} />
                <View name='account.email' className={cn(CARD_WIDTH, 'mt-8 sm:mt-0 sm:ml-8')} />
                <View name='account.twoFactor' className={cn(CARD_WIDTH, 'mt-8 md:mt-0 md:ml-8')} />
            </div>
            {(model.userAvatarsEnabled || model.languagesEnabled) && (
                <div className='flex flex-wrap lg:grid lg:grid-cols-3 mb-10'>
                    {model.userAvatarsEnabled && <View name='account.avatar' className={CARD_WIDTH} />}
                    {model.languagesEnabled && (
                        <View
                            name='account.language'
                            className={cn(CARD_WIDTH, model.userAvatarsEnabled && 'mt-8 sm:mt-0 sm:ml-8')}
                        />
                    )}
                </div>
            )}
            <Slot name='account.overview.after' />
        </PageContentBlock>
    );
}
