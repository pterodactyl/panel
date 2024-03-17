import { Form, Formik } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import Field, { FieldRow } from '@/components/elements/Field';
import SelectField from '@/components/elements/SelectField';
import { Button } from '@/components/elements/button';

export default () => {
    const submit = () => {
        //
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                httpConnectionTimeout: 5,
                httpRequestTimeout: 15,
                autoAllocation: 'false',
                autoAllocationPortsStart: 1024,
                autoAllocationPortsEnd: 65535,
            }}
        >
            <Form>
                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6`}>
                    <AdminBox title="HTTP Connections">
                        <FieldRow>
                            <Field
                                id={'httpConnectionTimeout'}
                                name={'httpConnectionTimeout'}
                                type={'number'}
                                label={'Connection Timeout'}
                                description={
                                    'The amount of time in seconds to wait for a connection to be opened before throwing an error.'
                                }
                            />
                            <Field
                                id={'httpRequestTimeout'}
                                name={'httpRequestTimeout'}
                                type={'number'}
                                label={'Request Timeout'}
                                description={
                                    'The amount of time in seconds to wait for a request to be completed before throwing an error.'
                                }
                            />
                        </FieldRow>
                    </AdminBox>
                    <AdminBox title="Automatic Allocation Creation">
                        <FieldRow css={tw`lg:grid-cols-3`}>
                            <SelectField
                                id={'autoAllocation'}
                                name={'autoAllocation'}
                                label={'Status'}
                                description={
                                    'If enabled users will have the option to automatically create new allocations for their server via the frontend.'
                                }
                                options={[
                                    { value: 'true', label: 'Enabled' },
                                    { value: 'false', label: 'Disabled' },
                                ]}
                            />
                            <Field
                                id={'autoAllocationPortsStart'}
                                name={'autoAllocationPortsStart'}
                                type={'number'}
                                label={'Starting port'}
                                description={'The starting port in the range that can be automatically allocated.'}
                            />
                            <Field
                                id={'autoAllocationPortsEnd'}
                                name={'autoAllocationPortsEnd'}
                                type={'number'}
                                label={'Ending port'}
                                description={'The ending port in the range that can be automatically allocated.'}
                            />
                        </FieldRow>
                    </AdminBox>
                </div>
                <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mt-6`}>
                    <div css={tw`flex flex-row`}>
                        <Button type="submit" css={tw`ml-auto`}>
                            Save Changes
                        </Button>
                    </div>
                </div>
            </Form>
        </Formik>
    );
};
