import type { FormikHelpers } from 'formik';
import { Form, Formik, useFormikContext } from 'formik';
import { useState } from 'react';
import tw from 'twin.macro';

import type { CreateEggVariable } from '@/api/admin/eggs/createEggVariable';
import createEggVariable from '@/api/admin/eggs/createEggVariable';
import { useEggFromRoute } from '@/api/admin/egg';
import { EggVariableForm, validationSchema } from '@/components/admin/nests/eggs/EggVariablesContainer';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Button } from '@/components/elements/button';
import useFlash from '@/plugins/useFlash';
import { Variant } from '@/components/elements/button/types';

export default function NewVariableButton() {
    const { setValues } = useFormikContext();
    const [visible, setVisible] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data: egg, mutate } = useEggFromRoute();

    if (!egg) {
        return null;
    }

    const submit = (values: CreateEggVariable, { setSubmitting }: FormikHelpers<CreateEggVariable>) => {
        clearFlashes('variable:create');

        createEggVariable(egg.id, values)
            .then(async variable => {
                setValues([...egg.relationships.variables, variable]);
                await mutate(egg => ({
                    ...egg!,
                    relationships: { ...egg!.relationships, variables: [...egg!.relationships.variables, variable] },
                }));
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
                    environmentVariable: '',
                    defaultValue: '',
                    isUserViewable: false,
                    isUserEditable: false,
                    rules: '',
                }}
                validationSchema={validationSchema}
            >
                {({ isSubmitting, isValid, resetForm }) => (
                    <Modal
                        visible={visible}
                        dismissable={!isSubmitting}
                        showSpinnerOverlay={isSubmitting}
                        onDismissed={() => {
                            resetForm();
                            setVisible(false);
                        }}
                    >
                        <FlashMessageRender byKey={'variable:create'} css={tw`mb-6`} />

                        <h2 css={tw`mb-6 text-2xl text-neutral-100`}>New Variable</h2>

                        <Form css={tw`m-0`}>
                            <EggVariableForm prefix={''} />

                            <div css={tw`flex flex-wrap justify-end mt-6`}>
                                <Button
                                    type="button"
                                    variant={Variant.Secondary}
                                    css={tw`w-full sm:w-auto sm:mr-2`}
                                    onClick={() => setVisible(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    css={tw`w-full mt-4 sm:w-auto sm:mt-0`}
                                    disabled={isSubmitting || !isValid}
                                >
                                    Create Variable
                                </Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>

            {/* TODO: make button green */}
            <Button type="button" onClick={() => setVisible(true)}>
                New Variable
            </Button>
        </>
    );
}
