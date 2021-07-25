import React, { forwardRef } from 'react';
import { Field as FormikField, FieldProps } from 'formik';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import InputError from '@/components/elements/InputError';

interface OwnProps {
    name: string;
    light?: boolean;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

const Field = forwardRef<HTMLInputElement, Props>(({ id, name, light = false, label, description, validate, ...props }, ref) => (
    <FormikField innerRef={ref} name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <div>
                    {label &&
                    <Label htmlFor={id} isLight={light}>{label}</Label>
                    }
                    <Input
                        id={id}
                        {...field}
                        {...props}
                        isLight={light}
                        hasError={!!(touched[field.name] && errors[field.name])}
                    />
                    <InputError errors={errors} touched={touched} name={field.name}>
                        {description || null}
                    </InputError>
                </div>
            )
        }
    </FormikField>
));
Field.displayName = 'Field';

export default Field;
