import { Form, Formik, useFormikContext } from 'formik';
import React from 'react';
import tw from 'twin.macro';
import { object } from 'yup';
import { Egg, EggVariable } from '@/api/admin/eggs/getEgg';
import AdminBox from '@/components/admin/AdminBox';
import Checkbox from '@/components/elements/Checkbox';
import Field, { FieldRow, TextareaField } from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

function EggVariableForm ({ variable: { name } }: { variable: EggVariable }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox css={tw`relative w-full`} title={<p css={tw`text-sm uppercase`}>{name}</p>}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={'name'}
                name={'name'}
                label={'Name'}
                type={'text'}
                css={tw`mb-6`}
            />

            <TextareaField
                id={'description'}
                name={'description'}
                label={'Description'}
                rows={3}
                css={tw`mb-4`}
            />

            <FieldRow>
                <Field
                    id={'envVariable'}
                    name={'envVariable'}
                    label={'Environment Variable'}
                    type={'text'}
                />

                <Field
                    id={'defaultValue'}
                    name={'defaultValue'}
                    label={'Default Value'}
                    type={'text'}
                />
            </FieldRow>

            <div css={tw`flex flex-row mb-6`}>
                <Checkbox
                    id={'userViewable'}
                    name={'userViewable'}
                    label={'User Viewable'}
                />

                <Checkbox
                    id={'userEditable'}
                    name={'userEditable'}
                    label={'User Editable'}
                    css={tw`ml-auto`}
                />
            </div>

            <Field
                id={'rules'}
                name={'rules'}
                label={'Validation Rules'}
                type={'text'}
                css={tw`mb-2`}
            />
        </AdminBox>
    );
}

export default function EggVariablesContainer ({ egg }: { egg: Egg }) {
    const submit = () => {
        //
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
            }}
            validationSchema={object().shape({
            })}
        >
            <Form>
                <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6`}>
                    {egg.relations?.variables?.map(v => <EggVariableForm key={v.id} variable={v}/>)}
                </div>
            </Form>
        </Formik>
    );
}
