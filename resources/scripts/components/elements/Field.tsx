import React from 'react';
import { Field as FormikField, FieldProps } from 'formik';
import classNames from 'classnames';

interface OwnProps {
    name: string;
    light?: boolean;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

const Field = ({ id, name, light = false, label, description, validate, className, ...props }: Props) => (
    <FormikField name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <React.Fragment>
                    {label &&
                    <label htmlFor={id} className={light ? undefined : 'input-dark-label'}>{label}</label>
                    }
                    <input
                        id={id}
                        {...field}
                        {...props}
                        className={classNames((className || (light ? 'input' : 'input-dark')), {
                            error: touched[field.name] && errors[field.name],
                        })}
                    />
                    {touched[field.name] && errors[field.name] ?
                        <p className={'input-help error'}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() + (errors[field.name] as string).slice(1)}
                        </p>
                        :
                        description ? <p className={'input-help'}>{description}</p> : null
                    }
                </React.Fragment>
            )
        }
    </FormikField>
);

export default Field;
