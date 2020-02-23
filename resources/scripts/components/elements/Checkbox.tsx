import React from 'react';
import { Field, FieldProps } from 'formik';

interface Props {
    name: string;
    value: string;
}

type OmitFields = 'name' | 'value' | 'type' | 'checked' | 'onChange';

type InputProps = Omit<React.DetailedHTMLProps<React.InputHTMLAttributes<HTMLInputElement>, HTMLInputElement>, OmitFields>;

const Checkbox = ({ name, value, ...props }: Props & InputProps) => (
    <Field name={name}>
        {({ field, form }: FieldProps) => {
            if (!Array.isArray(field.value)) {
                console.error('Attempting to mount a checkbox using a field value that is not an array.');

                return null;
            }

            return (
                <input
                    {...field}
                    {...props}
                    type={'checkbox'}
                    checked={(field.value || []).includes(value)}
                    onClick={() => form.setFieldTouched(field.name, true)}
                    onChange={e => {
                        const set = new Set(field.value);
                        set.has(value) ? set.delete(value) : set.add(value);

                        field.onChange(e);
                        form.setFieldValue(field.name, Array.from(set));
                    }}
                />
            );
        }}
    </Field>
);

export default Checkbox;
