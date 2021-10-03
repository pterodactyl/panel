import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import React, { useEffect } from 'react';
import tw from 'twin.macro';
import { array, boolean, object, string } from 'yup';
import { Egg, EggVariable } from '@/api/admin/eggs/getEgg';
import updateEggVariables from '@/api/admin/eggs/updateEggVariables';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
import Checkbox from '@/components/elements/Checkbox';
import Field, { FieldRow, TextareaField } from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';

function EggVariableForm ({ variable: { name }, i }: { variable: EggVariable, i: number }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox css={tw`relative w-full`} title={<p css={tw`text-sm uppercase`}>{name}</p>}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={`[${i}].name`}
                name={`[${i}].name`}
                label={'Name'}
                type={'text'}
                css={tw`mb-6`}
            />

            <TextareaField
                id={`[${i}].description`}
                name={`[${i}].description`}
                label={'Description'}
                rows={3}
                css={tw`mb-4`}
            />

            <FieldRow>
                <Field
                    id={`[${i}].envVariable`}
                    name={`[${i}].envVariable`}
                    label={'Environment Variable'}
                    type={'text'}
                />

                <Field
                    id={`[${i}].defaultValue`}
                    name={`[${i}].defaultValue`}
                    label={'Default Value'}
                    type={'text'}
                />
            </FieldRow>

            <div css={tw`flex flex-row mb-6`}>
                <Checkbox
                    id={`[${i}].userViewable`}
                    name={`[${i}].userViewable`}
                    label={'User Viewable'}
                />

                <Checkbox
                    id={`[${i}].userEditable`}
                    name={`[${i}].userEditable`}
                    label={'User Editable'}
                    css={tw`ml-auto`}
                />
            </div>

            <Field
                id={`[${i}].rules`}
                name={`[${i}].rules`}
                label={'Validation Rules'}
                type={'text'}
                css={tw`mb-2`}
            />
        </AdminBox>
    );
}

export default function EggVariablesContainer ({ egg }: { egg: Egg }) {
    const { clearAndAddHttpError } = useFlash();

    const submit = (values: EggVariable[], { setSubmitting }: FormikHelpers<EggVariable[]>) => {
        updateEggVariables(egg.id, values)
            .then(variables => console.log(variables))
            .catch(error => clearAndAddHttpError({ key: 'egg', error }))
            .then(() => setSubmitting(false));
    };

    useEffect(() => {
        console.log(egg.relations?.variables || []);
    }, []);

    return (
        <Formik
            onSubmit={submit}
            initialValues={egg.relations?.variables || []}
            validationSchema={array().of(
                object().shape({
                    name: string().required().min(1).max(191),
                    description: string(),
                    envVariable: string().required().min(1).max(191),
                    defaultValue: string(),
                    userViewable: boolean().required(),
                    userEditable: boolean().required(),
                    rules: string().required(),
                }),
            )}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`flex flex-col mb-16`}>
                        <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6`}>
                            {egg.relations?.variables?.map((v, i) => <EggVariableForm key={v.id} i={i} variable={v}/>)}
                        </div>

                        <div css={tw`bg-neutral-700 rounded shadow-md py-2 pr-6 mt-6`}>
                            <div css={tw`flex flex-row`}>
                                <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                    Save Changes
                                </Button>
                            </div>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
