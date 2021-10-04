import { Field as FormikField, FieldProps } from 'formik';
import React, { forwardRef } from 'react';
import tw, { styled } from 'twin.macro';
import Input, { Textarea } from '@/components/elements/Input';
import InputError from '@/components/elements/InputError';
import Label from '@/components/elements/Label';

interface OwnProps {
    name: string;
    light?: boolean;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;

    className?: string;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

const Field = forwardRef<HTMLInputElement, Props>(({ id, name, light = false, label, description, validate, className, ...props }, ref) => (
    <FormikField innerRef={ref} name={name} validate={validate}>
        {
            ({ field, form: { errors, touched } }: FieldProps) => (
                <div className={className}>
                    {label &&
                    <div css={tw`flex flex-row`} title={description}>
                        <Label htmlFor={id} isLight={light}>{label}</Label>
                        {/*{description && <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" css={tw`h-4 w-4 ml-1 cursor-pointer`}><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>}*/}
                    </div>
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

type TextareaProps = OwnProps & Omit<React.TextareaHTMLAttributes<HTMLTextAreaElement>, 'name'>;

export const TextareaField = forwardRef<HTMLTextAreaElement, TextareaProps>(
    function TextareaField ({ id, name, light = false, label, description, validate, className, ...props }, ref) {
        return (
            <FormikField innerRef={ref} name={name} validate={validate}>
                {
                    ({ field, form: { errors, touched } }: FieldProps) => (
                        <div className={className}>
                            {label && <Label htmlFor={id} isLight={light}>{label}</Label>}
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
                    )
                }
            </FormikField>
        );
    }
);
TextareaField.displayName = 'TextareaField';

export const FieldRow = styled.div`
    ${tw`grid grid-cols-1 sm:grid-cols-2 gap-x-6 mb-6`};

    & > div {
        ${tw`mb-6 sm:mb-0 sm:w-full sm:flex sm:flex-col`};
    }
`;
