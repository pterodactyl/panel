import React from 'react';
import capitalize from 'lodash-es/capitalize';
import { FormikErrors, FormikTouched } from 'formik';

interface Props {
    errors: FormikErrors<any>;
    touched: FormikTouched<any>;
    name: string;
    children?: React.ReactNode;
}

const InputError = ({ errors, touched, name, children }: Props) => (
    touched[name] && errors[name] ?
        <p className={'input-help error'}>
            {typeof errors[name] === 'string' ?
                capitalize(errors[name] as string)
                :
                capitalize((errors[name] as unknown as string[])[0])
            }
        </p>
        :
        <React.Fragment>
            {children}
        </React.Fragment>
);

export default InputError;
