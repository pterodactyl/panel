import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import { ServerContext } from '@/state/server';
import editServer from '@/api/server/editServer';
import Button from '@/components/elements/Button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';

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

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();

    const edit = (resource: string, amount: number) => {
        clearFlashes('server:edit');

        editServer(uuid, resource, amount)
            .then(() => addFlash({
                key: 'server:edit',
                type: 'success',
                message: 'Server resources have been edited successfully.',
            }))
            .catch(error => clearAndAddHttpError({ key: 'server:edit', error }));
    };

    return (
        <ServerContentBlock title={'Edit Server'}>
            <FlashMessageRender byKey={'server:edit'} css={tw`mb-4`}/>
            <TitledGreyBox title={'Edit your server instance'}>
                Using this utility, you can edit your server by changing the resource
                limits per resource. You can purchase more resources at the store in
                order to add them to your server.
            </TitledGreyBox>
            <Container css={tw`lg:grid lg:grid-cols-3 gap-4 my-10`}>
                <TitledGreyBox title={'Edit server CPU limit'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Cpu size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('cpu', 50);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('cpu', -50);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the amount of CPU assigned to the server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Limit cannot be lower than 50%.</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server RAM limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('memory', 1024);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('memory', -1024);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the amount of RAM assigned to the server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Limit cannot be lower than 1GB.</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server storage limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('disk', 1024);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('disk', -1024);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the amount of storage assigned to the server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Limit cannot be lower than 1GB.</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server port quantity'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Share2 size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('allocation_limit', 1);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('allocation_limit', -1);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the limit of ports assigned to the server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Limit cannot be lower than 1.</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server backup limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Archive size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('backup_limit', 1);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('backup_limit', -1);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the limit of backups assigned to the server.</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Edit server database limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Database size={40} />
                        <Button
                            isSecondary
                            color={'green'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('database_limit', 1);
                            }}
                        >
                            <Icon.Plus />
                        </Button>
                        <Button
                            isSecondary
                            color={'red'}
                            css={tw`ml-4`}
                            onClick={() => {
                                edit('database_limit', -1);
                            }}
                        >
                            <Icon.Minus />
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Change the limit of backups assigned to the server.</p>
                </TitledGreyBox>
            </Container>
        </ServerContentBlock>
    );
};
