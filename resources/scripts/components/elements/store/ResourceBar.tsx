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
}

export default ({ className, titles }: RowProps) => {
    const [resources, setResources] = useState<Resources>();

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    const ResourceBox = ({ title, description, icon, amount, toHuman }: BoxProps) => (
        <ContentBox title={titles ? title : undefined}>
            <Tooltip content={description}>
                <Wrapper>
                    {icon}
                    <span className={'mx-1'} />
                    {toHuman ? megabytesToHuman(amount) : amount}
                </Wrapper>
            </Tooltip>
        </ContentBox>
    );

    return (
        <StoreContainer className={classNames(className, 'j-right lg:grid lg:grid-cols-7 gap-x-6 gap-2')}>
            <ResourceBox
                title={'CPU'}
                description={'The amount of CPU (in %) you have available.'}
                icon={<Icon.Cpu />}
                // @ts-expect-error We hate strongly typed stuff.
                amount={resources.cpu + '%'}
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
