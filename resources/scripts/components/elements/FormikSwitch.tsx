import React from 'react';
import { Field, FieldProps } from 'formik';
import Switch, { SwitchProps } from '@/components/elements/Switch';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';

const FormikSwitch = ({ name, label, ...props }: SwitchProps) => {
    return (
        <FormikFieldWrapper name={name}>
            <Field name={name}>
                {({ field, form }: FieldProps) => (
                    <Switch
                        name={name}
                        label={label}
                        onChange={() => {
                            form.setFieldTouched(name);
                            form.setFieldValue(field.name, !field.value);
                        }}
                        defaultChecked={field.value}
                        {...props}
                    />
                )}
            </Field>
        </FormikFieldWrapper>
    );
};

export default FormikSwitch;
