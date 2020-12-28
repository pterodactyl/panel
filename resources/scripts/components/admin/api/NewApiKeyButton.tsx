import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';
import { object } from 'yup';

interface Values {
    description: string,
}

const schema = object().shape({

});

export default () => {
    const [ visible, setVisible ] = useState(false);
    const { clearFlashes } = useFlash();

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('api:create');

        console.log(values);
        setSubmitting(true);

        setTimeout(() => {
            setVisible(false);
        }, 500);
    };

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{ description: '' }}
                validationSchema={schema}
            >
                {
                    ({ isSubmitting, resetForm }) => (
                        <Modal
                            visible={visible}
                            dismissable={!isSubmitting}
                            showSpinnerOverlay={isSubmitting}
                            onDismissed={() => {
                                resetForm();
                                setVisible(false);
                            }}
                        >
                            <FlashMessageRender byKey={'api:create'} css={tw`mb-6`}/>
                            <h2 css={tw`text-2xl mb-6`}>New API Key</h2>
                            <Form css={tw`m-0`}>
                                <Field
                                    type={'string'}
                                    id={'description'}
                                    name={'description'}
                                    label={'Description'}
                                    description={'A descriptive note for this API Key.'}
                                />

                                <div css={tw`w-full flex flex-col mt-6`}>
                                    <div css={tw`h-10 w-full flex flex-row items-center bg-neutral-900 rounded-t-md px-4`}>
                                        <p css={tw`text-sm text-neutral-300 uppercase`}>Permissions</p>

                                        <div css={tw`flex flex-row space-x-4 ml-auto`}>
                                            <span css={tw`text-xs text-neutral-300 cursor-pointer`}>None</span>
                                            <span css={tw`text-xs text-neutral-300 cursor-pointer`}>Read</span>
                                            <span css={tw`text-xs text-neutral-300 cursor-pointer`}>Write</span>
                                        </div>
                                    </div>

                                    <div css={tw`w-full flex flex-col bg-neutral-700 rounded-b-md py-1 px-4`}>
                                        <div css={tw`w-full flex flex-row items-center py-1`}>
                                            <p css={tw`text-sm text-neutral-200`}>Allocations</p>

                                            <div css={tw`flex space-x-6 ml-auto`}>
                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'allocations'} css={tw`h-5 w-5`} value={'0'}/>
                                                </div>

                                                <div css={tw`flex`}>
                                                    <input type={'radio'} name={'allocations'} css={tw`h-5 w-5`} value={'1'}/>
                                                </div>

                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'allocations'} css={tw`h-5 w-5`} value={'2'}/>
                                                </div>
                                            </div>
                                        </div>

                                        <div css={tw`w-full flex flex-row items-center py-1`}>
                                            <p css={tw`text-sm text-neutral-200`}>Databases</p>

                                            <div css={tw`flex space-x-6 ml-auto`}>
                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'databases'} css={tw`h-5 w-5`} value={'0'}/>
                                                </div>

                                                <div css={tw`flex`}>
                                                    <input type={'radio'} name={'databases'} css={tw`h-5 w-5`} value={'1'}/>
                                                </div>

                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'databases'} css={tw`h-5 w-5`} value={'2'}/>
                                                </div>
                                            </div>
                                        </div>

                                        <div css={tw`w-full flex flex-row items-center py-1`}>
                                            <p css={tw`text-sm text-neutral-200`}>Eggs</p>

                                            <div css={tw`flex space-x-6 ml-auto`}>
                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'eggs'} css={tw`h-5 w-5`} value={'0'}/>
                                                </div>

                                                <div css={tw`flex`}>
                                                    <input type={'radio'} name={'eggs'} css={tw`h-5 w-5`} value={'1'}/>
                                                </div>

                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'eggs'} css={tw`h-5 w-5`} value={'2'}/>
                                                </div>
                                            </div>
                                        </div>

                                        <div css={tw`w-full flex flex-row items-center py-1`}>
                                            <p css={tw`text-sm text-neutral-200`}>Locations</p>

                                            <div css={tw`flex space-x-6 ml-auto`}>
                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'locations'} css={tw`h-5 w-5`} value={'0'}/>
                                                </div>

                                                <div css={tw`flex`}>
                                                    <input type={'radio'} name={'locations'} css={tw`h-5 w-5`} value={'1'}/>
                                                </div>

                                                <div css={tw`flex pr-1`}>
                                                    <input type={'radio'} name={'locations'} css={tw`h-5 w-5`} value={'2'}/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
                                        Create API Key
                                    </Button>
                                </div>
                            </Form>
                        </Modal>
                    )
                }
            </Formik>

            <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`} onClick={() => setVisible(true)}>
                New API Key
            </Button>
        </>
    );
};
