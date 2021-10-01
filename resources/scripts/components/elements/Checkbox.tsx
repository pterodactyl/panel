import Label from '@/components/elements/Label';
import React from 'react';
import { Field, FieldProps } from 'formik';
import Input from '@/components/elements/Input';
import tw from 'twin.macro';

interface Props {
    name: string;
    label?: string;
    className?: string;
}

type OmitFields = 'ref' | 'name' | 'value' | 'type';

type InputProps = Omit<JSX.IntrinsicElements['input'], OmitFields>;

const Checkbox = ({ name, label, className, ...props }: Props & InputProps) => (
    <Field name={name}>
        {({ field }: FieldProps) => {
            return (
                <div css={tw`flex flex-row`} className={className}>
                    <Input
                        {...field}
                        {...props}
                        css={tw`w-5 h-5 mr-2`}
                        type={'checkbox'}
                    />
                    {label &&
                    <div css={tw`flex-1`}>
                        <Label noBottomSpacing>{label}</Label>
                    </div>}
                </div>
            );
        }}
    </Field>
);

export default Checkbox;
