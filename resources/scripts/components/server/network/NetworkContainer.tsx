import React, { useEffect } from 'react';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import styled from 'styled-components/macro';
import PageContentBlock from '@/components/elements/PageContentBlock';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';
import useServer from '@/plugins/useServer';
import useSWR from 'swr';
import getServerAllocations from '@/api/server/network/getServerAllocations';
import { Allocation } from '@/api/server/getServer';
import Spinner from '@/components/elements/Spinner';
import setPrimaryServerAllocation from '@/api/server/network/setPrimaryServerAllocation';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

const NetworkContainer = () => {
    const server = useServer();
    const { clearFlashes, clearAndAddError } = useFlash();
    const { data, error, mutate } = useSWR<Allocation[]>(server.uuid, key => getServerAllocations(key), { initialData: server.allocations });

    const setPrimaryAllocation = (ip: string, port: number) => {
        clearFlashes('server:network');

        mutate(data?.map(a => (a.ip === ip && a.port === port) ? { ...a, isDefault: true } : { ...a, isDefault: false }), false);

        setPrimaryServerAllocation(server.uuid, ip, port)
            .catch(error => clearAndAddError({ key: 'server:network', message: httpErrorToHuman(error) }));
    };

    useEffect(() => {
        if (error) {
            clearAndAddError({ key: 'server:network', message: error });
        }
    }, [ error ]);

    return (
        <PageContentBlock showFlashKey={'server:network'}>
            {!data ?
                <Spinner size={'large'} centered/>
                :
                data.map(({ ip, port, alias, isDefault }, index) => (
                    <GreyRowBox key={`${ip}:${port}`} css={index > 0 ? tw`mt-2` : undefined}>
                        <div css={tw`pl-4 pr-6 text-neutral-400`}>
                            <FontAwesomeIcon icon={faNetworkWired}/>
                        </div>
                        <div css={tw`mr-4`}>
                            <Code>{alias || ip}</Code>
                            <Label>IP Address</Label>
                        </div>
                        <div>
                            <Code>:{port}</Code>
                            <Label>Port</Label>
                        </div>
                        <div css={tw`flex-1 text-right`}>
                            {isDefault ?
                                <span css={tw`bg-green-500 py-1 px-2 rounded text-green-50 text-xs`}>
                                    Primary
                                </span>
                                :
                                <Can action={'allocations.update'}>
                                    <Button
                                        isSecondary
                                        size={'xsmall'}
                                        color={'primary'}
                                        onClick={() => setPrimaryAllocation(ip, port)}
                                    >
                                        Make Primary
                                    </Button>
                                </Can>
                            }
                        </div>
                    </GreyRowBox>
                ))
            }
        </PageContentBlock>
    );
};

export default NetworkContainer;
