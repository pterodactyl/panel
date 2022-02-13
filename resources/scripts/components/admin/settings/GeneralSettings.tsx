import Field, { FieldRow } from '@/components/elements/Field';
import { Form, Formik } from 'formik';
import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';

export default () => {
    const submit = () => {
        //
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{}}
        >
            <Form>
                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6`}>
                    <AdminBox title="Branding">
                        <FieldRow>
                            <Field
                                id={'appName'}
                                name={'appName'}
                                type={'text'}
                                label={'App Name'}
                                description={''}
                            />
                        </FieldRow>
                    </AdminBox>

                    <AdminBox title="Analytics">
                        <FieldRow>
                            <Field
                                id={'googleAnalytics'}
                                name={'googleAnalytics'}
                                type={'text'}
                                label={'Google Analytics'}
                                description={''}
                            />
                        </FieldRow>
                    </AdminBox>
                </div>
            </Form>
        </Formik>
    );
};
