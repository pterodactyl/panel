import tw from 'twin.macro';
import * as React from 'react';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import ContentBox from '@/components/elements/ContentBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import AccountLogContainer from '@/components/dashboard/AccountLogContainer';
import UpdatePasswordForm from '@/components/dashboard/forms/UpdatePasswordForm';

const Container = styled.div`
  ${tw`flex flex-wrap`};

  & > div {
    ${tw`w-full`};

    ${breakpoint('sm')`
      width: calc(50% - 1rem);
    `}

    ${breakpoint('md')`
      ${tw`w-auto flex-1`};
    `}
  }
`;

export default () => {
    return (
        <PageContentBlock title={'Account Security'}>
            <Container css={tw`lg:grid lg:grid-cols-2 mb-10 mt-10`}>
                <ContentBox title={'Update Password'} css={tw`flex-none w-full md:w-1/2`} showFlashes={'account:password'}>
                    <UpdatePasswordForm />
                </ContentBox>
                <ContentBox title={'Session Logs'} css={tw`ml-8`} showFlashes={'account:logs'}>
                    <AccountLogContainer />
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
