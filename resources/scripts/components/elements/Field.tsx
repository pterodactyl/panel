import React from 'react';
import { Field, FieldProps } from 'formik';
import classNames from 'classnames';

interface Props {
    id?: string;
    type: string;
    name: string;
    label?: string;
    description?: string;
    autoFocus?: boolean;
    validate?: (value: any) => undefined | string | Promise<any>;
}

export default ({ id, type, name, label, description, autoFocus, validate }: Props) => (
    <Field name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <React.Fragment>
                    {label &&
                    <label htmlFor={id} className={'input-dark-label'}>{label}</label>
                    }
                    <input
                        id={id}
                        type={type}
                        {...field}
                        autoFocus={autoFocus}
                        className={classNames('input-dark', {
                            error: touched[field.name] && errors[field.name],
                        })}
                    />
                    {touched[field.name] && errors[field.name] ?
                        <p className={'input-help'}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() + (errors[field.name] as string).slice(1)}
                        </p>
                        :
                        description ? <p className={'input-help'}>{description}</p> : null
                    }
                </React.Fragment>
            )
        }
    </Field>
);
