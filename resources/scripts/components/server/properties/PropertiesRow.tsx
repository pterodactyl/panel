import React from 'react';
import { capitalize } from '@/lib/strings';
import Field from '@/components/elements/Field';
import Switch from '@/components/elements/Switch';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Select from '@/components/elements/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useFormikContext, Field as FormikField } from 'formik';

interface Props {
    item: {
        key: string;
        value: string;
        type: string;
        values: string[];
    };
}

export default ({ item }: Props) => {
    const { setFieldValue, values } = useFormikContext();

    return (
        <TitledGreyBox title={capitalize(item.key)}>
            {item.type === 'string' && <Field name={item.key} />}
            {item.type === 'switch' && (
                <div style={{ paddingBottom: '0.235rem' }}>
                    <Switch
                        name={item.key}
                        label={capitalize(item.key)}
                        description={'Toggle for true / false'}
                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                        // @ts-ignore
                        defaultChecked={values[item.key]}
                        onChange={() => {
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            setFieldValue(item.key, !values[item.key]);
                        }}
                    />
                </div>
            )}
            {item.type === 'select' && (
                <FormikFieldWrapper name={item.key}>
                    <FormikField as={Select} name={item.key}>
                        {item.values.map((item, key) => (
                            <option key={key} value={item}>
                                {capitalize(item)}
                            </option>
                        ))}
                    </FormikField>
                </FormikFieldWrapper>
            )}
        </TitledGreyBox>
    );
};
