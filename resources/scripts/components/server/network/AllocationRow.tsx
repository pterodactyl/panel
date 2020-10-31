import React, { memo, useState } from 'react';
import isEqual from 'react-fast-compare';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import InputSpinner from '@/components/elements/InputSpinner';
import { Textarea } from '@/components/elements/Input';
import Can from '@/components/elements/Can';
import Button from '@/components/elements/Button';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { Allocation } from '@/api/server/getServer';
import styled from 'styled-components/macro';
import { debounce } from 'debounce';
import setServerAllocationNotes from '@/api/server/network/setServerAllocationNotes';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import CopyOnClick from '@/components/elements/CopyOnClick';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

interface Props {
    allocation: Allocation;
    onSetPrimary: (id: number) => void;
    onNotesChanged: (id: number, notes: string) => void;
}

const AllocationRow = ({ allocation, onSetPrimary, onNotesChanged }: Props) => {
    const [ loading, setLoading ] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const setAllocationNotes = debounce((notes: string) => {
        setLoading(true);
        clearFlashes('server:network');

        setServerAllocationNotes(uuid, allocation.id, notes)
            .then(() => onNotesChanged(allocation.id, notes))
            .catch(error => clearAndAddHttpError({ key: 'server:network', error }))
            .then(() => setLoading(false));
    }, 750);

    return (
        <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-no-wrap mt-2`}>
            <div css={tw`flex items-center w-full md:w-auto`}>
                <div css={tw`pl-4 pr-6 text-neutral-400`}>
                    <FontAwesomeIcon icon={faNetworkWired} />
                </div>
                <div css={tw`mr-4 flex-1 md:w-40`}>
                    {allocation.alias ? <CopyOnClick text={allocation.alias}><Code css={tw`w-40 truncate`}>{allocation.alias}</Code></CopyOnClick> :
                        <CopyOnClick text={allocation.ip}><Code>{allocation.ip}</Code></CopyOnClick>}
                    <Label>{allocation.alias ? 'Hostname' : 'IP Address'}</Label>
                </div>
                <div css={tw`w-16 md:w-24 overflow-hidden`}>
                    <Code>{allocation.port}</Code>
                    <Label>Port</Label>
                </div>
            </div>
            <div css={tw`mt-4 w-full md:mt-0 md:flex-1 md:w-auto`}>
                <InputSpinner visible={loading}>
                    <Textarea
                        css={tw`bg-neutral-800 hover:border-neutral-600 border-transparent`}
                        placeholder={'Notes'}
                        defaultValue={allocation.notes || undefined}
                        onChange={e => setAllocationNotes(e.currentTarget.value)}
                    />
                </InputSpinner>
            </div>
            <div css={tw`w-full md:flex-none md:w-32 md:text-center mt-4 md:mt-0 text-right ml-4`}>
                {allocation.isDefault ?
                    <span css={tw`bg-green-500 py-1 px-2 rounded text-green-50 text-xs`}>Primary</span>
                    :
                    <Can action={'allocations.update'}>
                        <Button
                            isSecondary
                            size={'xsmall'}
                            color={'primary'}
                            onClick={() => onSetPrimary(allocation.id)}
                        >
                            Make Primary
                        </Button>
                    </Can>
                }
            </div>
        </GreyRowBox>
    );
};

export default memo(AllocationRow, isEqual);
