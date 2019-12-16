import React from 'react';
import { Field, FieldProps } from 'formik';
import classNames from 'classnames';

interface OwnProps {
    name: string;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

export default ({ id, name, label, description, validate, className, ...props }: Props) => (
    <Field name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <React.Fragment>
                    {label &&
                    <label htmlFor={id} className={'input-dark-label'}>{label}</label>
                    }
                    <input
                        id={id}
                        {...field}
                        {...props}
                        className={classNames((className || 'input-dark'), {
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
    </Field>
);
