import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import React, { useState, useEffect } from 'react';
import Spinner from '@/components/elements/Spinner';
import { Button } from '@/components/elements/button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { Link } from 'react-router-dom';

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

const Wrapper = styled.div`
    ${tw`text-2xl flex flex-row justify-center items-center`};
`;

const OverviewContainer = () => {
    const [resources, setResources] = useState<Resources>();
    const username = useStoreState((state) => state.user.data!.username);

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <h1 className={'j-left text-5xl'}>👋 Hey, {username}!</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>Welcome to the Jexactyl storefront.</h3>
            <Container className={'j-right lg:grid lg:grid-cols-3 my-10'}>
                <TitledGreyBox title={'Total CPU'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Cpu css={tw`mr-2`} /> {resources.cpu}%
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart css={tw`mr-2`} /> {megabytesToHuman(resources.memory)}
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive css={tw`mr-2`} /> {megabytesToHuman(resources.disk)}
                    </Wrapper>
                </TitledGreyBox>
            </Container>
            <Container className={'j-left lg:grid lg:grid-cols-4 my-10'}>
                <TitledGreyBox title={'Total Slots'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Server css={tw`mr-2`} /> {resources.slots}
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Ports'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Share2 css={tw`mr-2`} /> {resources.ports}
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Backups'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Archive css={tw`mr-2`} /> {resources.backups}
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Databases'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Database css={tw`mr-2`} /> {resources.databases}
                    </Wrapper>
                </TitledGreyBox>
            </Container>
            <div className={'text-center mr-10'}>
                <p className={'text-xl my-2 text-gray-400'}>Want to create your next server?</p>
                <Link to={'/store/create'}>
                    <Button className={'w-full lg:w-1/6'}>
                        <Icon.PlusCircle className={'mr-1'} />
                        Create
                    </Button>
                </Link>
            </div>
        </PageContentBlock>
    );
};

export default OverviewContainer;
