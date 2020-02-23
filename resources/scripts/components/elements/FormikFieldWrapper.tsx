import React from 'react';
import { Field, FieldProps } from 'formik';
import classNames from 'classnames';
import InputError from '@/components/elements/InputError';

interface Props {
    name: string;
    children: React.ReactNode;
    className?: string;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

const FormikFieldWrapper = ({ name, label, className, description, validate, children }: Props) => (
    <Field name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <div className={classNames(className, { 'has-error': touched[field.name] && errors[field.name] })}>
                    {label && <label htmlFor={name}>{label}</label>}
                    {children}
                    <InputError errors={errors} touched={touched} name={field.name}>
                        {description ? <p className={'input-help'}>{description}</p> : null}
                    </InputError>
                </div>
            )
        }
    </Field>
);

export default FormikFieldWrapper;
