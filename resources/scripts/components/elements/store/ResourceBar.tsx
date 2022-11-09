import tw from 'twin.macro';
import classNames from 'classnames';
import * as Icon from 'react-feather';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import React, { useState, useEffect } from 'react';
import Spinner from '@/components/elements/Spinner';
import ContentBox from '@/components/elements/ContentBox';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import StoreContainer from '@/components/elements/StoreContainer';
import { getResources, Resources } from '@/api/store/getResources';

const Wrapper = styled.div`
    ${tw`text-2xl flex flex-row justify-center items-center`};
`;

interface RowProps {
    className?: string;
    titles?: boolean;
}

interface BoxProps {
    title: string;
    description: string;
    icon: React.ReactElement;
    amount: number;
    toHuman?: boolean;
    suffix?: string;
}

export default ({ className, titles }: RowProps) => {
    const [resources, setResources] = useState<Resources>();

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    const ResourceBox = (props: BoxProps) => (
        <ContentBox title={titles ? props.title : undefined}>
            <Tooltip content={props.description}>
                <Wrapper>
                    {props.icon}
                    <span className={'ml-2'}>{props.toHuman ? megabytesToHuman(props.amount) : props.amount}</span>
                    {props.suffix}
                </Wrapper>
            </Tooltip>
        </ContentBox>
    );

    return (
        <StoreContainer className={classNames(className, 'j-right grid grid-cols-2 sm:grid-cols-7 gap-x-6 gap-y-2')}>
            <ResourceBox
                title={'Credits'}
                description={'The amount of credits you have available.'}
                icon={<Icon.DollarSign />}
                amount={resources.balance}
            />
            <ResourceBox
                title={'CPU'}
                description={'The amount of CPU (in %) you have available.'}
                icon={<Icon.Cpu />}
                amount={resources.cpu}
                suffix={'%'}
            />
            <ResourceBox
                title={'Memory'}
                description={'The amount of RAM (in MB/GB) you have available.'}
                icon={<Icon.PieChart />}
                amount={resources.memory}
                toHuman
            />
            <ResourceBox
                title={'Disk'}
                description={'The amount of storage (in MB/GB) you have available.'}
                icon={<Icon.HardDrive />}
                amount={resources.disk}
                toHuman
            />
            <ResourceBox
                title={'Slots'}
                description={'The amount of servers you are able to deploy.'}
                icon={<Icon.Server />}
                amount={resources.slots}
            />
            <ResourceBox
                title={'Ports'}
                description={'The amount of ports you can add to your servers.'}
                icon={<Icon.Share2 />}
                amount={resources.ports}
            />
            <ResourceBox
                title={'Backups'}
                description={'The amount of backup slots you can add to your servers.'}
                icon={<Icon.Archive />}
                amount={resources.backups}
            />
            <ResourceBox
                title={'Databases'}
                description={'The amount of database slots you can add to your servers.'}
                icon={<Icon.Database />}
                amount={resources.databases}
            />
        </StoreContainer>
    );
};
