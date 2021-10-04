import React, { memo, useState } from 'react';
import { ServerEggVariable } from '@/api/server/types';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { usePermissions } from '@/plugins/usePermissions';
import InputSpinner from '@/components/elements/InputSpinner';
import Input from '@/components/elements/Input';
import tw from 'twin.macro';
import { debounce } from 'debounce';
import updateStartupVariable from '@/api/server/updateStartupVariable';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import getServerStartup from '@/api/swr/getServerStartup';
import isEqual from 'react-fast-compare';
import { ServerContext } from '@/state/server';

interface Props {
    variable: ServerEggVariable;
}

const VariableBox = ({ variable }: Props) => {
    const FLASH_KEY = `server:startup:${variable.envVariable}`;

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const [ loading, setLoading ] = useState(false);
    const [ canEdit ] = usePermissions([ 'startup.update' ]);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerStartup(uuid);

    const setVariableValue = debounce((value: string) => {
        setLoading(true);
        clearFlashes(FLASH_KEY);

        updateStartupVariable(uuid, variable.envVariable, value)
            .then(([ response, invocation ]) => mutate(data => ({
                ...data,
                invocation,
                variables: (data.variables || []).map(v => v.envVariable === response.envVariable ? response : v),
            }), false))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: FLASH_KEY });
            })
            .then(() => setLoading(false));
    }, 500);

    return (
        <TitledGreyBox
            title={
                <p css={tw`text-sm uppercase`}>
                    {!variable.isEditable &&
                    <span css={tw`bg-neutral-700 text-xs py-1 px-2 rounded-full mr-2 mb-1`}>Read Only</span>
                    }
                    {variable.name}
                </p>
            }
        >
            <FlashMessageRender byKey={FLASH_KEY} css={tw`mb-2 md:mb-4`}/>
            <InputSpinner visible={loading}>
                <Input
                    onKeyUp={e => {
                        if (canEdit && variable.isEditable) {
                            setVariableValue(e.currentTarget.value);
                        }
                    }}
                    readOnly={!canEdit || !variable.isEditable}
                    name={variable.envVariable}
                    defaultValue={variable.serverValue}
                    placeholder={variable.defaultValue}
                />
            </InputSpinner>
            <p css={tw`mt-1 text-xs text-neutral-300`}>
                {variable.description}
            </p>
        </TitledGreyBox>
    );
};

export default memo(VariableBox, isEqual);
