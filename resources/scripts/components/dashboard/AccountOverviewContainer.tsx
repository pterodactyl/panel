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
import { withTranslation, WithTranslation } from 'react-i18next';

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

const AccountOverviewContainer = ({ t }: WithTranslation) => {
    const { state } = useLocation<undefined | { twoFactorRedirect?: boolean }>();

    return (
        <PageContentBlock title={'Account Overview'}>
            {state?.twoFactorRedirect &&
            <MessageBox title={t('2fa_required_title')} type={'error'}>
                {t('2fa_required_desc')}
            </MessageBox>
            }
            <Container css={[ tw`mb-10`, state?.twoFactorRedirect ? tw`mt-4` : tw`mt-10` ]}>
                <ContentBox title={t('update_password')} showFlashes={'account:password'}>
                    <UpdatePasswordForm/>
                </ContentBox>
                <ContentBox
                    css={tw`mt-8 md:mt-0 md:ml-8`}
                    title={t('update_email')}
                    showFlashes={'account:email'}
                >
                    <UpdateEmailAddressForm/>
                </ContentBox>
                <ContentBox css={tw`xl:ml-8 mt-8 xl:mt-0`} title={t('configure_2fa')}>
                    <ConfigureTwoFactorForm/>
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};

export default withTranslation('dashboard')(AccountOverviewContainer);
