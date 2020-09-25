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

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm block`}`;
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
        <GreyRowBox
            $hoverable={false}
            // css={index > 0 ? tw`mt-2 overflow-x-auto` : tw`overflow-x-auto`}
        >
            <div css={tw`hidden md:block pl-4 pr-6 text-neutral-400`}>
                <FontAwesomeIcon icon={faNetworkWired}/>
            </div>
            <div css={tw`mr-4`}>
                <Code>{allocation.alias || allocation.ip}</Code>
                <Label>IP Address</Label>
            </div>
            <div>
                <Code>{allocation.port}</Code>
                <Label>Port</Label>
            </div>
            <div css={tw`px-8 flex-none sm:flex-1 self-start`}>
                <InputSpinner visible={loading}>
                    <Textarea
                        css={tw`bg-neutral-800 hover:border-neutral-600 border-transparent`}
                        placeholder={'Notes'}
                        defaultValue={allocation.notes || undefined}
                        onChange={e => setAllocationNotes(e.currentTarget.value)}
                    />
                </InputSpinner>
            </div>
            <div css={tw`w-32 text-right pr-4 sm:pr-0`}>
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
