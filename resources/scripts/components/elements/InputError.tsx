import React from 'react';
import { FormikErrors, FormikTouched } from 'formik';
import tw from 'twin.macro';
import { capitalize } from '@/lib/strings';

interface Props {
    errors: FormikErrors<any>;
    touched: FormikTouched<any>;
    name: string;
    children?: string | number | null | undefined;
}

const InputError = ({ errors, touched, name, children }: Props) =>
    touched[name] && errors[name] ? (
        <p css={tw`text-xs text-red-400 pt-2`}>
            {typeof errors[name] === 'string'
                ? capitalize(errors[name] as string)
                : capitalize((errors[name] as unknown as string[])[0])}
        </p>
    ) : (
        <>{children ? <p css={tw`text-xs text-neutral-400 pt-2`}>{children}</p> : null}</>
    );

export default InputError;
