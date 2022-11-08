import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import React, { useState, useEffect } from 'react';
import Spinner from '@/components/elements/Spinner';
import ContentBox from '@/components/elements/ContentBox';
import StoreContainer from '@/components/elements/StoreContainer';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';

const Wrapper = styled.div`
    ${tw`text-2xl flex flex-row justify-center items-center`};
`;

export default () => {
    const [resources, setResources] = useState<Resources>();
    const appName = useStoreState((state) => state.settings.data!.name);
    const username = useStoreState((state) => state.user.data!.username);

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <h1 className={'j-left text-5xl'}>👋 Hey, {username}!</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>Welcome to the {appName} store.</h3>
            <StoreContainer className={'j-right lg:grid lg:grid-cols-3 gap-6 my-10'}>
                <ContentBox title={'Total CPU'}>
                    <Wrapper>
                        <Icon.Cpu className={'mr-2'} /> {resources.cpu}%
                    </Wrapper>
                </ContentBox>
                <ContentBox title={'Total RAM'}>
                    <Wrapper>
                        <Icon.PieChart className={'mr-2'} /> {megabytesToHuman(resources.memory)}
                    </Wrapper>
                </ContentBox>
                <ContentBox title={'Total Disk'}>
                    <Wrapper>
                        <Icon.HardDrive className={'mr-2'} /> {megabytesToHuman(resources.disk)}
                    </Wrapper>
                </ContentBox>
            </StoreContainer>
            <StoreContainer className={'j-left lg:grid lg:grid-cols-4 gap-6 my-10'}>
                <ContentBox title={'Total Slots'}>
                    <Wrapper>
                        <Icon.Server className={'mr-2'} /> {resources.slots}
                    </Wrapper>
                </ContentBox>
                <ContentBox title={'Total Ports'}>
                    <Wrapper>
                        <Icon.Share2 className={'mr-2'} /> {resources.ports}
                    </Wrapper>
                </ContentBox>
                <ContentBox title={'Total Backups'}>
                    <Wrapper>
                        <Icon.Archive className={'mr-2'} /> {resources.backups}
                    </Wrapper>
                </ContentBox>
                <ContentBox title={'Total Databases'}>
                    <Wrapper>
                        <Icon.Database className={'mr-2'} /> {resources.databases}
                    </Wrapper>
                </ContentBox>
            </StoreContainer>
        </PageContentBlock>
    );
};
