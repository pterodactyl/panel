import deleteEggVariable from '@/api/admin/eggs/deleteEggVariable';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import React from 'react';
import tw from 'twin.macro';
import { array, boolean, object, string } from 'yup';
import getEgg, { Egg, EggVariable } from '@/api/admin/eggs/getEgg';
import updateEggVariables from '@/api/admin/eggs/updateEggVariables';
import NewVariableButton from '@/components/admin/nests/eggs/NewVariableButton';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
import Checkbox from '@/components/elements/Checkbox';
import Field, { FieldRow, TextareaField } from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';

export const validationSchema = object().shape({
    name: string().required().min(1).max(191),
    description: string(),
    envVariable: string().required().min(1).max(191),
    defaultValue: string(),
    userViewable: boolean().required(),
    userEditable: boolean().required(),
    rules: string().required(),
});

export function EggVariableForm ({ prefix }: { prefix: string }) {
    return (
        <>
            <Field
                id={`${prefix}name`}
                name={`${prefix}name`}
                label={'Name'}
                type={'text'}
                css={tw`mb-6`}
            />

            <TextareaField
                id={`${prefix}description`}
                name={`${prefix}description`}
                label={'Description'}
                rows={3}
                css={tw`mb-4`}
            />

            <FieldRow>
                <Field
                    id={`${prefix}envVariable`}
                    name={`${prefix}envVariable`}
                    label={'Environment Variable'}
                    type={'text'}
                />

                <Field
                    id={`${prefix}defaultValue`}
                    name={`${prefix}defaultValue`}
                    label={'Default Value'}
                    type={'text'}
                />
            </FieldRow>

            <div css={tw`flex flex-row mb-6`}>
                <Checkbox
                    id={`${prefix}userViewable`}
                    name={`${prefix}userViewable`}
                    label={'User Viewable'}
                />

                <Checkbox
                    id={`${prefix}userEditable`}
                    name={`${prefix}userEditable`}
                    label={'User Editable'}
                    css={tw`ml-auto`}
                />
            </div>

            <Field
                id={`${prefix}rules`}
                name={`${prefix}rules`}
                label={'Validation Rules'}
                type={'text'}
                css={tw`mb-2`}
            />
        </>
    );
}

function EggVariableBox ({ onDeleteClick, variable, prefix }: { onDeleteClick: () => void, variable: EggVariable, prefix: string }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox
            css={tw`relative w-full`}
            title={<p css={tw`text-sm uppercase`}>{variable.name}</p>}
            button={
                <button
                    type={'button'}
                    css={tw`ml-auto text-neutral-500 hover:text-neutral-300`}
                    onClick={onDeleteClick}
                >
                    {/* TODO: Change to heroicon 'x' */}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" css={tw`h-5 w-5`}>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            }
        >
            <SpinnerOverlay visible={isSubmitting}/>

            <EggVariableForm prefix={prefix}/>
        </AdminBox>
    );
}

export default function EggVariablesContainer ({ egg }: { egg: Egg }) {
    const { clearAndAddHttpError } = useFlash();

    const { mutate } = getEgg(egg.id);

    const submit = (values: EggVariable[], { setSubmitting }: FormikHelpers<EggVariable[]>) => {
        updateEggVariables(egg.id, values)
            .then(async (variables) => await mutate(egg => ({ ...egg!, relations: { variables: variables } })))
            .catch(error => clearAndAddHttpError({ key: 'egg', error }))
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={egg.relations?.variables || []}
            validationSchema={array().of(validationSchema)}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`flex flex-col mb-16`}>
                        <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6`}>
                            {egg.relations?.variables?.map((v, i) => (
                                <EggVariableBox
                                    key={i}
                                    prefix={`[${i}].`}
                                    variable={v}
                                    onDeleteClick={() => {
                                        deleteEggVariable(egg.id, v.id)
                                            .then(async () => await mutate(egg => ({
                                                ...egg!,
                                                relations: {
                                                    variables: egg!.relations.variables!.filter(v2 => v.id === v2.id),
                                                },
                                            })))
                                            .catch(error => clearAndAddHttpError({ key: 'egg', error }));
                                    }}
                                />
                            ))}
                        </div>

                        <div css={tw`bg-neutral-700 rounded shadow-md py-2 px-4 mt-6`}>
                            <div css={tw`flex flex-row`}>
                                <NewVariableButton eggId={egg.id}/>

                                <Button type={'submit'} size={'small'} css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
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
