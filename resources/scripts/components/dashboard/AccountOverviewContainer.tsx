import ContentBox from '@/components/elements/ContentBox';
import UpdatePasswordForm from '@/components/dashboard/forms/UpdatePasswordForm';
import UpdateEmailAddressForm from '@/components/dashboard/forms/UpdateEmailAddressForm';
import ConfigureTwoFactorForm from '@/components/dashboard/forms/ConfigureTwoFactorForm';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { cn } from '@/lib/cn';
import { Alert } from '@/components/elements/alert';
import { useLocation } from '@tanstack/react-router';
import AccountOverviewCardGrid from '@/components/dashboard/AccountOverviewCardGrid';
import Slot from '@/extensions/Slot';
import PageHeading from '@/components/elements/PageHeading';

const CARD_WIDTH = 'w-full sm:w-[calc(50%_-_1rem)] md:w-auto md:flex-1';

export default function AccountOverviewContainer() {
    const state = useLocation().state as { twoFactorRedirect?: boolean } | null;

    return (
        <PageContentBlock title='Account Overview'>
            <PageHeading title='Account' description='Manage your profile, credentials, and account security.' />
            {state?.twoFactorRedirect && (
                <Alert title='2-Factor Required' type='danger'>
                    Your account must have two-factor authentication enabled in order to continue.
                </Alert>
            )}

            <Slot name='account.overview.before' />
            <AccountOverviewCardGrid className={cn('lg:grid lg:grid-cols-3 mb-10', state?.twoFactorRedirect && 'mt-4')}>
                <ContentBox className={CARD_WIDTH} title='Update Password'>
                    <UpdatePasswordForm />
                </ContentBox>
                <ContentBox className={cn(CARD_WIDTH, 'mt-8 sm:mt-0 sm:ml-8')} title='Update Email Address'>
                    <UpdateEmailAddressForm />
                </ContentBox>
                <ContentBox className={cn(CARD_WIDTH, 'md:ml-8 mt-8 md:mt-0')} title='Two-Step Verification'>
                    <ConfigureTwoFactorForm />
                </ContentBox>
            </AccountOverviewCardGrid>
            <Slot name='account.overview.after' />
        </PageContentBlock>
    );
}
