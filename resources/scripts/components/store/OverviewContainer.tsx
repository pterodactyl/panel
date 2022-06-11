import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import { Link } from 'react-router-dom';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import Button from '@/components/elements/Button';
import React, { useState, useEffect } from 'react';
import PlusSquareSvg from '@/assets/images/plus_square.svg';
import StoreError from '@/components/store/error/StoreError';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import DivideSquareSvg from '@/assets/images/divide_square.svg';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';

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
    const [ resources, setResources ] = useState<Resources>();
    const username = useStoreState(state => state.user.data!.username);

    useEffect(() => {
        getResources()
            .then(resources => setResources(resources));
    }, []);

    const redirect = (url: string) => {
        // @ts-ignore
        window.location = `/store/${url}`;
    };

    if (!resources) return <StoreError />;

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <h1 css={tw`text-5xl`}>👋 Hey, {username}!</h1>
            <h3 css={tw`text-2xl mt-2 text-neutral-500`}>Welcome to the Jexactyl storefront.</h3>
            <Container css={tw`lg:grid lg:grid-cols-3 my-10`}>
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
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
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
            <Container css={tw`lg:grid lg:grid-cols-2 my-10`}>
                <TitledGreyBox title={'Create server'}>
                    <div css={tw`md:flex w-full p-6 md:pl-0 mx-1`}>
                        <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
                            <img src={PlusSquareSvg} css={tw`block w-32 md:w-48 mx-auto p-8`}/>
                        </div>
                        <div css={tw`flex-1`}>
                            <h2 css={tw`text-xl mb-2`}>Create a server</h2>
                            <p>
                                Configure and create your next server with your choice of
                                resource limits, server type and more. Delete or edit your
                                server at any time to take full advantage of your resources.
                            </p>
                            <Link to={'/store/create'}>
                                <Button
                                    css={tw`mt-6`}
                                    size={'xlarge'}
                                >
                                    Create
                                </Button>
                            </Link>
                        </div>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <div css={tw`md:flex w-full p-6 md:pl-0 mx-1`}>
                        <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
                            <img src={DivideSquareSvg} css={tw`block w-32 md:w-48 mx-auto p-8`}/>
                        </div>
                        <div css={tw`flex-1`}>
                            <h2 css={tw`text-xl mb-2`}>Edit your servers</h2>
                            <p>
                                Want to add or remove resources from your server,
                                or delete it entirely? Use the editing feature to
                                make changes to your server instantly.
                            </p>
                            <Button
                                css={tw`mt-6`}
                                size={'xlarge'}
                                onClick={() => redirect('edit')}
                            >
                                Edit
                            </Button>
                        </div>
                    </div>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};

export default OverviewContainer;
