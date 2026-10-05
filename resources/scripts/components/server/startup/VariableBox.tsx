import { useState } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { usePermissions } from '@/plugins/usePermissions';
import InputSpinner from '@/components/elements/InputSpinner';
import { TextInput } from '@/components/form/controls';
import Switch from '@/components/ui/Switch';
import Select from '@/components/ui/Select';
import { useCurrentServerUuid } from '@/api/server/queries';
import { type ServerStartupVariable, useUpdateStartupVariable } from '@/api/server/startup/queries';
import { useDebouncedCallback } from '@/plugins/useDebouncedCallback';

interface Props {
    variable: ServerStartupVariable;
}

const VariableEditor = ({ variable, uuid }: Props & { uuid: string }) => {
    const { attributes } = variable;
    const [draft, setDraft] = useState<string | null>(null);
    const value = draft ?? attributes.server_value ?? attributes.default_value;
    const [canEdit] = usePermissions(['startup.update']);
    const updateVariable = useUpdateStartupVariable(uuid);

    const saveVariable = useDebouncedCallback((value: string) => {
        updateVariable.mutate(
            { path: { server_uuid: uuid }, body: { key: attributes.env_variable, value } },
            { onSuccess: () => setDraft((current) => (current === value ? null : current)) }
        );
    }, 500);

    const setVariableValue = (value: string) => {
        setDraft(value);
        saveVariable(value);
    };

    const rules = attributes.rules.split('|');
    const useSwitch = rules.some(
        (v) => v === 'boolean' || v === 'in:0,1' || v === 'in:1,0' || v === 'in:true,false' || v === 'in:false,true'
    );
    const isStringSwitch = rules.some((v) => v === 'string');
    const selectValues = rules.find((v) => v.startsWith('in:'))?.split(',') || [];

    return (
        <TitledGreyBox
            title={
                <p className='text-sm uppercase'>
                    {!attributes.is_editable && (
                        <span className='bg-card text-xs py-1 px-2 rounded-full mr-2 mb-1'>Read Only</span>
                    )}
                    {attributes.name}
                </p>
            }
        >
            <InputSpinner visible={updateVariable.isPending}>
                {useSwitch ? (
                    <>
                        <Switch
                            disabled={!canEdit || !attributes.is_editable}
                            checked={isStringSwitch ? value === 'true' : value === '1'}
                            onChange={() => {
                                if (canEdit && attributes.is_editable) {
                                    if (isStringSwitch) {
                                        setVariableValue(value === 'true' ? 'false' : 'true');
                                    } else {
                                        setVariableValue(value === '1' ? '0' : '1');
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
                                    value={value}
                                    onChange={(value) => setVariableValue(String(value))}
                                    disabled={!canEdit || !attributes.is_editable}
                                    options={selectValues.map((rule) => {
                                        const clean = rule.replace('in:', '');
                                        return { value: clean, label: clean };
                                    })}
                                />
                            </>
                        ) : (
                            <>
                                <TextInput
                                    onChange={(e) => {
                                        if (canEdit && attributes.is_editable) {
                                            setVariableValue(e.currentTarget.value);
                                        }
                                    }}
                                    onBlur={saveVariable.flush}
                                    readOnly={!canEdit || !attributes.is_editable}
                                    name={attributes.env_variable}
                                    value={value}
                                    placeholder={attributes.default_value}
                                />
                            </>
                        )}
                    </>
                )}
            </InputSpinner>

            <p className='mt-1 text-xs text-muted-foreground'>{attributes.description}</p>
        </TitledGreyBox>
    );
};

const VariableBox = ({ variable }: Props) => {
    const uuid = useCurrentServerUuid()!;
    return <VariableEditor key={`${uuid}:${variable.attributes.env_variable}`} uuid={uuid} variable={variable} />;
};

export default VariableBox;
