import { Form, Formik, FormikHelpers } from 'formik';
import React, { useState } from 'react';
import tw from 'twin.macro';
import createEggVariable from '@/api/admin/eggs/createEggVariable';
import getEgg from '@/api/admin/eggs/getEgg';
import { EggVariableForm, validationSchema } from '@/components/admin/nests/eggs/EggVariablesContainer';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import useFlash from '@/plugins/useFlash';

export default function NewVariableButton ({ eggId }: { eggId: number }) {
    const [ visible, setVisible ] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { mutate } = getEgg(eggId);

    const submit = (values: any, { setSubmitting }: FormikHelpers<any>) => {
        clearFlashes('variable:create');

        createEggVariable(eggId, values)
            .then(async (v) => {
                await mutate(egg => ({ ...egg!, relations: { variables: [ ...egg!.relations.variables!, v ] } }));
                setVisible(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'variable:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{
                    name: '',
                    description: '',
                    envVariable: '',
                    defaultValue: '',
                    userViewable: false,
                    userEditable: false,
                    rules: '',
                }}
                validationSchema={validationSchema}
            >
                {({ isSubmitting, resetForm }) => (
                    <Modal
                        visible={visible}
                        dismissable={!isSubmitting}
                        showSpinnerOverlay={isSubmitting}
                        onDismissed={() => {
                            resetForm();
                            setVisible(false);
                        }}
                    >
                        <FlashMessageRender byKey={'variable:create'} css={tw`mb-6`}/>

                        <h2 css={tw`mb-6 text-2xl text-neutral-100`}>New Variable</h2>

                        <Form css={tw`m-0`}>
                            <EggVariableForm prefix={''}/>

                            <div css={tw`flex flex-wrap justify-end mt-6`}>
                                <Button
                                    type={'button'}
                                    isSecondary
                                    css={tw`w-full sm:w-auto sm:mr-2`}
                                    onClick={() => setVisible(false)}
                                >
                                    Cancel
                                </Button>
                                <Button css={tw`w-full mt-4 sm:w-auto sm:mt-0`} type={'submit'}>
                                    Create Variable
                                </Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>

            <Button type={'button'} color={'green'} onClick={() => setVisible(true)}>
                New Variable
            </Button>
        </>
    );
}
