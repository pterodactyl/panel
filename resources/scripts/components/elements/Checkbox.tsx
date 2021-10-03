import { Field } from 'formik';
import React from 'react';
import tw from 'twin.macro';
import Label from '@/components/elements/Label';

interface Props {
    id: string;
    name: string;
    label?: string;
    className?: string;
}

const Checkbox = ({ id, name, label, className }: Props) => (
    <div css={tw`flex flex-row`} className={className}>
        <Field type={'checkbox'} id={id} name={name} css={[ label && tw`mr-2` ]}/>
        {label &&
        <div css={tw`flex-1`}>
            <Label noBottomSpacing>{label}</Label>
        </div>}
    </div>
);

export default Checkbox;
