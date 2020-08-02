import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';
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
import { Textarea } from '@/components/elements/Input';
import setServerAllocationNotes from '@/api/server/network/setServerAllocationNotes';
import { debounce } from 'debounce';
import InputSpinner from '@/components/elements/InputSpinner';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

const NetworkContainer = () => {
    const { uuid, allocations, name: serverName } = useServer();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ loading, setLoading ] = useState<false | number>(false);
    const { data, error, mutate } = useSWR<Allocation[]>(uuid, key => getServerAllocations(key), { initialData: allocations });

    const setPrimaryAllocation = (id: number) => {
        clearFlashes('server:network');

        const initial = data;
        mutate(data?.map(a => a.id === id ? { ...a, isDefault: true } : { ...a, isDefault: false }), false);

        setPrimaryServerAllocation(uuid, id)
            .catch(error => {
                clearAndAddHttpError({ key: 'server:network', error });
                mutate(initial, false);
            });
    };

    const setAllocationNotes = debounce((id: number, notes: string) => {
        setLoading(id);
        clearFlashes('server:network');

        setServerAllocationNotes(uuid, id, notes)
            .then(() => mutate(data?.map(a => a.id === id ? { ...a, notes } : a), false))
            .catch(error => {
                clearAndAddHttpError({ key: 'server:network', error });
            })
            .then(() => setLoading(false));
    }, 750);

    useEffect(() => {
        if (error) {
            clearAndAddHttpError({ key: 'server:network', error });
        }
    }, [ error ]);

    return (
        <PageContentBlock showFlashKey={'server:network'}>
            <Helmet>
                <title> {serverName} | Network </title>
            </Helmet>
            {!data ?
                <Spinner size={'large'} centered/>
                :
                data.map(({ id, ip, port, alias, notes, isDefault }, index) => (
                    <GreyRowBox key={`${ip}:${port}`} css={index > 0 ? tw`mt-2` : undefined} $hoverable={false}>
                        <div css={tw`pl-4 pr-6 text-neutral-400`}>
                            <FontAwesomeIcon icon={faNetworkWired}/>
                        </div>
                        <div css={tw`mr-4`}>
                            <Code>{alias || ip}</Code>
                            <Label>IP Address</Label>
                        </div>
                        <div>
                            <Code>{port}</Code>
                            <Label>Port</Label>
                        </div>
                        <div css={tw`px-8 flex-1 self-start`}>
                            <InputSpinner visible={loading === id}>
                                <Textarea
                                    css={tw`bg-neutral-800 hover:border-neutral-600 border-transparent`}
                                    placeholder={'Notes'}
                                    defaultValue={notes || undefined}
                                    onChange={e => setAllocationNotes(id, e.currentTarget.value)}
                                />
                            </InputSpinner>
                        </div>
                        <div css={tw`w-32 text-right`}>
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
                                        onClick={() => setPrimaryAllocation(id)}
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
