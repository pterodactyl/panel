import tw from 'twin.macro';
import * as Icon from 'react-feather';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import React, { useState, useEffect } from 'react';
import Spinner from '@/components/elements/Spinner';
import ContentBox from '@/components/elements/ContentBox';
import StoreContainer from '@/components/elements/StoreContainer';
import { getResources, Resources } from '@/api/store/getResources';
import classNames from 'classnames';

const Wrapper = styled.div`
    ${tw`text-2xl flex flex-row justify-center items-center`};
`;

interface Props {
    className?: string;
    titles?: boolean;
}

export default ({ className, titles }: Props) => {
    const [resources, setResources] = useState<Resources>();

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    return (
        <StoreContainer className={classNames(className, 'j-right lg:grid lg:grid-cols-7 gap-x-6')}>
            <ContentBox title={titles ? 'CPU' : undefined}>
                <Wrapper>
                    <Icon.Cpu className={'mr-2'} /> {resources.cpu}%
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Memory' : undefined}>
                <Wrapper>
                    <Icon.PieChart className={'mr-2'} /> {megabytesToHuman(resources.memory)}
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Disk' : undefined}>
                <Wrapper>
                    <Icon.HardDrive className={'mr-2'} /> {megabytesToHuman(resources.disk)}
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Slots' : undefined}>
                <Wrapper>
                    <Icon.Server className={'mr-2'} /> {resources.slots}
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Ports' : undefined}>
                <Wrapper>
                    <Icon.Share2 className={'mr-2'} /> {resources.ports}
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Backups' : undefined}>
                <Wrapper>
                    <Icon.Archive className={'mr-2'} /> {resources.backups}
                </Wrapper>
            </ContentBox>
            <ContentBox title={titles ? 'Databases' : undefined}>
                <Wrapper>
                    <Icon.Database className={'mr-2'} /> {resources.databases}
                </Wrapper>
            </ContentBox>
        </StoreContainer>
    );
};
