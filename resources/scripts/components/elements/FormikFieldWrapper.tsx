import React from 'react';
import { Field, FieldProps } from 'formik';
import classNames from 'classnames';
import InputError from '@/components/elements/InputError';
import Label from '@/components/elements/Label';
import tw from 'twin.macro';

interface Props {
    id?: string;
    name: string;
    children: React.ReactNode;
    className?: string;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

const FormikFieldWrapper = ({ id, name, label, className, description, validate, children }: Props) => (
    <Field name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <div className={classNames(className, { 'has-error': touched[field.name] && errors[field.name] })}>
                    {label && <Label htmlFor={id}>{label}</Label>}
                    {children}
                    <InputError errors={errors} touched={touched} name={field.name}>
                        {description || null}
                    </InputError>
                </div>
            )
        }
    </Field>
);

export default FormikFieldWrapper;
