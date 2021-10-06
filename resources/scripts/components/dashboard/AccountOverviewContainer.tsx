import * as React from 'react';
import ContentBox from '@/components/elements/ContentBox';
import UpdatePasswordForm from '@/components/dashboard/forms/UpdatePasswordForm';
import UpdateEmailAddressForm from '@/components/dashboard/forms/UpdateEmailAddressForm';
import ConfigureTwoFactorForm from '@/components/dashboard/forms/ConfigureTwoFactorForm';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import MessageBox from '@/components/MessageBox';
import { useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const Container = styled.div`
    ${tw`flex flex-wrap`};

    & > div {
        ${tw`w-full`};

        ${breakpoint('md')`
            width: calc(50% - 1rem);
        `}

        ${breakpoint('xl')`
            ${tw`w-auto flex-1`};
        `}
    }
`;

export default () => {
    const { t } = useTranslation();
    const { state } = useLocation<undefined | { twoFactorRedirect?: boolean }>();

    return (
        <PageContentBlock title={t('Account Overview')}>
            {state?.twoFactorRedirect &&
            <MessageBox title={t('2-Factor Required')} type={'error'}>
                {t('Two-factor Required')}
            </MessageBox>
            }
            <Container css={[ tw`mb-10`, state?.twoFactorRedirect ? tw`mt-4` : tw`mt-10` ]}>
                <ContentBox title={t('Update Password')} showFlashes={'account:password'}>
                    <UpdatePasswordForm/>
                </ContentBox>
                <ContentBox
                    css={tw`mt-8 md:mt-0 md:ml-8`}
                    title={t('Update Email Address')}
                    showFlashes={'account:email'}
                >
                    <UpdateEmailAddressForm/>
                </ContentBox>
                <ContentBox css={tw`xl:ml-8 mt-8 xl:mt-0`} title={t('Configure Two Factor')}>
                    <ConfigureTwoFactorForm/>
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
