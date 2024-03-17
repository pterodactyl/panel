import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import { Button } from '@/components/elements/button';
import Field, { FieldRow } from '@/components/elements/Field';
import Label from '@/components/elements/Label';
import { Context } from './SettingsRouter';
import SelectField from '@/components/elements/SelectField';
import { updateSetting } from '@/api/admin/settings';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

interface Values {
    smtpHost: string;
    smtpPort: number;
    smtpEncryption: string;
    smtpUsername: string;
    smtpPassword: string;
    smtpMailFrom: string;
    smtpMailFromName: string;
}

export default () => {
    const { host, port, encryption, username, password, fromAddress, fromName } = Context.useStoreState(
        state => state.settings!.mail,
    );

    const { addFlash, clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('admin:settings');
        setSubmitting(true);

        try {
            await updateSetting(values);
            addFlash({ type: 'success', message: 'Successfully updated settings.', key: 'admin:settings' });
            setTimeout(() => clearFlashes('admin:settings'), 2000);
        } catch (error) {
            console.error(error);
            clearAndAddHttpError({ key: 'admin:settings', error });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                smtpHost: host ?? 'smtp.example.com',
                smtpPort: port ?? 587,
                smtpEncryption: encryption ?? 'tls',
                smtpUsername: username ?? '',
                smtpPassword: password ?? '',
                smtpMailFrom: fromAddress ?? 'no-reply@example.com',
                smtpMailFromName: fromName ?? 'Pterodactyl Panel',
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
                                <SelectField
                                    id={'smtpEncryption'}
                                    name={'smtpEncryption'}
                                    options={[
                                        { value: 'ssl', label: 'Secure Sockets Layer (SSL)' },
                                        { value: 'tls', label: 'Transport Layer Security (TLS)' },
                                    ]}
                                />
                            </div>
                        </FieldRow>

                        <FieldRow>
                            <Field
                                id={'smtpUsername'}
                                name={'smtpUsername'}
                                type={'text'}
                                label={'Username'}
                                description={''}
                            />
                            <Field
                                id={'smtpPassword'}
                                name={'smtpPassword'}
                                type={'password'}
                                label={'Password'}
                                description={''}
                            />
                        </FieldRow>

                        <FieldRow>
                            <Field
                                id={'smtpMailFrom'}
                                name={'smtpMailFrom'}
                                type={'text'}
                                label={'Mail From'}
                                description={''}
                            />
                            <Field
                                id={'smtpMailFromName'}
                                name={'smtpMailFromName'}
                                type={'text'}
                                label={'Mail From Name'}
                                description={''}
                            />
                        </FieldRow>
                    </AdminBox>

                    <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mt-6`}>
                        <div css={tw`flex flex-row`}>
                            <Button type="submit" className="ml-auto" disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
};
