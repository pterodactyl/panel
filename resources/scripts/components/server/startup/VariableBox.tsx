import tw from 'twin.macro';
import { debounce } from 'debounce';
import isEqual from 'react-fast-compare';
import useFlash from '@/plugins/useFlash';
import React, { memo, useState } from 'react';
import { ServerContext } from '@/state/server';
import Input from '@/components/elements/Input';
import Switch from '@/components/elements/Switch';
import Select from '@/components/elements/Select';
import { ServerEggVariable } from '@/api/server/types';
import { usePermissions } from '@/plugins/usePermissions';
import getServerStartup from '@/api/swr/getServerStartup';
import InputSpinner from '@/components/elements/InputSpinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import updateStartupVariable from '@/api/server/updateStartupVariable';

interface Props {
    variable: ServerEggVariable;
}

const VariableBox = ({ variable }: Props) => {
    const FLASH_KEY = `server:startup:${variable.envVariable}`;

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [canEdit] = usePermissions(['startup.update']);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerStartup(uuid);

    const setVariableValue = debounce((value: string) => {
        setLoading(true);
        clearFlashes(FLASH_KEY);

        updateStartupVariable(uuid, variable.envVariable, value)
            .then(([response, invocation]) =>
                mutate(
                    (data) => ({
                        ...data,
                        invocation,
                        variables: (data.variables || []).map((v) =>
                            v.envVariable === response.envVariable ? response : v
                        ),
                    }),
                    false
                )
            )
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ error, key: FLASH_KEY });
            })
            .then(() => setLoading(false));
    }, 500);

    const useSwitch = variable.rules.some(
        (v) => v === 'boolean' || v === 'in:0,1' || v === 'in:1,0' || v === 'in:true,false' || v === 'in:false,true'
    );
    const isStringSwitch = variable.rules.some((v) => v === 'string');
    const selectValues = variable.rules.find((v) => v.startsWith('in:'))?.split(',') || [];

    return (
        <TitledGreyBox
            title={
                <p css={tw`text-sm uppercase`}>
                    {!variable.isEditable && (
                        <span css={tw`bg-neutral-700 text-xs py-1 px-2 rounded-full mr-2 mb-1`}>Read Only</span>
                    )}
                    {variable.name}
                </p>
            }
        >
            <FlashMessageRender byKey={FLASH_KEY} css={tw`mb-2 md:mb-4`} />
            <InputSpinner visible={loading}>
                {useSwitch ? (
                    <>
                        <Switch
                            readOnly={!canEdit || !variable.isEditable}
                            name={variable.envVariable}
                            defaultChecked={
                                isStringSwitch ? variable.serverValue === 'true' : variable.serverValue === '1'
                            }
                            onChange={() => {
                                if (canEdit && variable.isEditable) {
                                    if (isStringSwitch) {
                                        setVariableValue(variable.serverValue === 'true' ? 'false' : 'true');
                                    } else {
                                        setVariableValue(variable.serverValue === '1' ? '0' : '1');
                                    }
                                }
                            }}
                        />
                    </>
                ) : (
                    <>
                        {selectValues.length > 0 ? (
                            <>
                                <Select
                                    onChange={(e) => setVariableValue(e.target.value)}
                                    name={variable.envVariable}
                                    defaultValue={variable.serverValue}
                                    disabled={!canEdit || !variable.isEditable}
                                >
                                    {selectValues.map((selectValue) => (
                                        <option
                                            key={selectValue.replace('in:', '')}
                                            value={selectValue.replace('in:', '')}
                                        >
                                            {selectValue.replace('in:', '')}
                                        </option>
                                    ))}
                                </Select>
                            </>
                        ) : (
                            <>
                                <Input
                                    onKeyUp={(e) => {
                                        if (canEdit && variable.isEditable) {
                                            setVariableValue(e.currentTarget.value);
                                        }
                                    }}
                                    readOnly={!canEdit || !variable.isEditable}
                                    name={variable.envVariable}
                                    defaultValue={variable.serverValue}
                                    placeholder={variable.defaultValue}
                                />
                            </>
                        )}
                    </>
                )}
            </InputSpinner>
            <p css={tw`mt-1 text-xs text-neutral-300`}>{variable.description}</p>
        </TitledGreyBox>
    );
};

export default memo(VariableBox, isEqual);
