import { Form, Formik } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
import Field, { FieldRow } from '@/components/elements/Field';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';

export default () => {
    const submit = () => {
        //
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                smtpHost: 'smtp.example.com',
                smtpPort: 587,
                smtpEncryption: 'tls',
                username: '',
                password: '',
                mailFrom: 'no-reply@example.com',
                mailFromName: 'Pterodactyl Panel',
            }}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <AdminBox title="Mail">
                        <FieldRow css={tw`lg:grid-cols-3`}>
                            <Field
                                id={'smtpHost'}
                                name={'smtpHost'}
                                type={'text'}
                                label={'SMTP Host'}
                                description={''}
                            />
                            <Field
                                id={'smtpPort'}
                                name={'smtpPort'}
                                type={'number'}
                                label={'SMTP Port'}
                                description={''}
                            />
                            <div>
                                <Label>Encryption</Label>
                                <Select id={'smtpEncryption'} name={'smtpEncryption'} defaultValue={'tls'}>
                                    <option value="">None</option>
                                    <option value="ssl">Secure Sockets Layer (SSL)</option>
                                    <option value="tls">Transport Layer Security (TLS)</option>
                                </Select>
                            </div>
                        </FieldRow>

                        <FieldRow>
                            <Field
                                id={'username'}
                                name={'username'}
                                type={'text'}
                                label={'Username'}
                                description={''}
                            />
                            <Field
                                id={'password'}
                                name={'password'}
                                type={'password'}
                                label={'Password'}
                                description={''}
                            />
                        </FieldRow>

                        <FieldRow>
                            <Field
                                id={'mailFrom'}
                                name={'mailFrom'}
                                type={'text'}
                                label={'Mail From'}
                                description={''}
                            />
                            <Field
                                id={'mailFromName'}
                                name={'mailFromName'}
                                type={'text'}
                                label={'Mail From Name'}
                                description={''}
                            />
                        </FieldRow>
                    </AdminBox>

                    <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mt-6`}>
                        <div css={tw`flex flex-row`}>
                            <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
};
