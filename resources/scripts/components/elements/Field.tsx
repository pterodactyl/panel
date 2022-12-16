import type { FieldProps } from 'formik';
import { Field as FormikField } from 'formik';
import type { InputHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { forwardRef } from 'react';
import tw, { styled } from 'twin.macro';

import Label from '@/components/elements/Label';
import Input, { Textarea } from '@/components/elements/Input';
import InputError from '@/components/elements/InputError';

interface OwnProps {
    name: string;
    light?: boolean;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<InputHTMLAttributes<HTMLInputElement>, 'name'>;

const Field = forwardRef<HTMLInputElement, Props>(
    ({ id, name, light = false, label, description, validate, ...props }, ref) => (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {({ field, form: { errors, touched } }: FieldProps) => (
                <div>
                    {label && (
                        <Label htmlFor={id} isLight={light}>
                            {label}
                        </Label>
                    )}
                    <Input
                        id={id}
                        {...field}
                        {...props}
                        isLight={light}
                        hasError={!!(touched[field.name] && errors[field.name])}
                    />
                    {touched[field.name] && errors[field.name] ? (
                        <p className={'input-help error'}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() +
                                (errors[field.name] as string).slice(1)}
                        </p>
                    ) : description ? (
                        <p className={'input-help'}>{description}</p>
                    ) : null}
                </div>
            )}
        </FormikField>
    ),
);
Field.displayName = 'Field';

export default Field;

type TextareaProps = OwnProps & Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'name'>;

export const TextareaField = forwardRef<HTMLTextAreaElement, TextareaProps>(function TextareaField(
    { id, name, light = false, label, description, validate, className, ...props },
    ref,
) {
    return (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {({ field, form: { errors, touched } }: FieldProps) => (
                <div className={className}>
                    {label && (
                        <Label htmlFor={id} isLight={light}>
                            {label}
                        </Label>
                    )}
                    <Textarea
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
            )}
        </FormikField>
    );
});
TextareaField.displayName = 'TextareaField';

export const FieldRow = styled.div`
    ${tw`grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6 mb-6`};
    & > div {
        ${tw`sm:w-full sm:flex sm:flex-col`};
    }
`;
