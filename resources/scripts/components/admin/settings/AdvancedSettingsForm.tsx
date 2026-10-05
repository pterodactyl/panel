import { useAppForm, Form } from '@/components/form';
import type { AdminSettings } from '@/api/admin/settings/queries';
import { updateAdminAdvancedSettingsInput, useUpdateAdminAdvancedSettings } from '@/api/admin/settings/queries';
import { type NumberInputValue, requiredNumber, submittedNumber } from '@/components/admin/numberInput';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Alert } from '@/components/elements/alert';

interface AdvancedSettingsFormValues {
    recaptchaEnabled: boolean;
    recaptchaSecretKey: string;
    recaptchaWebsiteKey: string;
    guzzleTimeout: NumberInputValue;
    guzzleConnectTimeout: NumberInputValue;
    allocationsEnabled: boolean;
    allocationsRangeStart: string;
    allocationsRangeEnd: string;
}

const advancedSettingsFormValues = (settings: AdminSettings): AdvancedSettingsFormValues => ({
    recaptchaEnabled: settings.advanced['recaptcha:enabled'],
    recaptchaSecretKey: settings.advanced['recaptcha:secret_key'],
    recaptchaWebsiteKey: settings.advanced['recaptcha:website_key'],
    guzzleTimeout: settings.advanced['pterodactyl:guzzle:timeout'],
    guzzleConnectTimeout: settings.advanced['pterodactyl:guzzle:connect_timeout'],
    allocationsEnabled: settings.advanced['pterodactyl:client_features:allocations:enabled'],
    allocationsRangeStart: String(settings.advanced['pterodactyl:client_features:allocations:range_start'] ?? ''),
    allocationsRangeEnd: String(settings.advanced['pterodactyl:client_features:allocations:range_end'] ?? ''),
});

export default function AdvancedSettingsForm({ settings }: { settings: AdminSettings }) {
    const readOnly = settings.meta.load_environment_only;
    const updateAdvancedSettings = useUpdateAdminAdvancedSettings();

    const form = useAppForm({
        defaultValues: advancedSettingsFormValues(settings),
        onSubmit: async ({ value }) => {
            if (readOnly) {
                return;
            }

            try {
                await updateAdvancedSettings.mutateAsync(
                    updateAdminAdvancedSettingsInput({
                        recaptchaEnabled: value.recaptchaEnabled,
                        recaptchaSecretKey: value.recaptchaSecretKey,
                        recaptchaWebsiteKey: value.recaptchaWebsiteKey,
                        guzzleTimeout: submittedNumber(value.guzzleTimeout),
                        guzzleConnectTimeout: submittedNumber(value.guzzleConnectTimeout),
                        allocationsEnabled: value.allocationsEnabled,
                        allocationsRangeStart:
                            value.allocationsRangeStart === '' ? '' : Number(value.allocationsRangeStart),
                        allocationsRangeEnd: value.allocationsRangeEnd === '' ? '' : Number(value.allocationsRangeEnd),
                    })
                );
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <Form form={form}>
            <TitledGreyBox title={'reCAPTCHA'}>
                {settings.meta.show_recaptcha_warning && (
                    <Alert type={'warning'} className={'mb-6 text-sm'}>
                        <span>
                            You are using reCAPTCHA keys that shipped with this Panel. Generate new invisible reCAPTCHA
                            keys for this website to improve security.
                        </span>
                    </Alert>
                )}
                <form.AppField name={'recaptchaEnabled'}>
                    {(field) => (
                        <field.SwitchField
                            label={'reCAPTCHA Enabled'}
                            description={'Protect login and registration forms with reCAPTCHA.'}
                            disabled={readOnly}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6'}>
                    <form.AppField name={'recaptchaWebsiteKey'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'recaptcha_website_key'}
                                label={'reCAPTCHA Website Key'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'mt-6'}>
                    <form.AppField name={'recaptchaSecretKey'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'recaptcha_secret_key'}
                                label={'reCAPTCHA Secret Key'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                </div>
            </TitledGreyBox>
            <TitledGreyBox title={'HTTP Connections'} className={'mt-6'}>
                <div className={'grid grid-cols-2 gap-4'}>
                    <form.AppField
                        name={'guzzleTimeout'}
                        validators={{ onChange: requiredNumber('An HTTP request timeout must be provided.') }}
                    >
                        {(field) => (
                            <field.NumberField
                                id={'guzzle_timeout'}
                                label={'HTTP Request Timeout'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                    <form.AppField
                        name={'guzzleConnectTimeout'}
                        validators={{ onChange: requiredNumber('An HTTP connection timeout must be provided.') }}
                    >
                        {(field) => (
                            <field.NumberField
                                id={'guzzle_connect_timeout'}
                                label={'HTTP Connection Timeout'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                </div>
            </TitledGreyBox>
            <TitledGreyBox title={'Automatic Allocation Creation'} className={'mt-6'}>
                <form.AppField name={'allocationsEnabled'}>
                    {(field) => (
                        <field.SwitchField
                            label={'Auto Create Allocations Enabled'}
                            description={'Allow users to automatically create new allocations for their server.'}
                            disabled={readOnly}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6 grid grid-cols-2 gap-4'}>
                    <form.AppField name={'allocationsRangeStart'}>
                        {(field) => (
                            <field.TextField
                                type={'number'}
                                id={'allocations_range_start'}
                                label={'Starting Port'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'allocationsRangeEnd'}>
                        {(field) => (
                            <field.TextField
                                type={'number'}
                                id={'allocations_range_end'}
                                label={'Ending Port'}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                </div>
            </TitledGreyBox>
            <div className={'flex justify-end mt-6'}>
                <form.AppForm>
                    <form.SubmitButton disabled={readOnly}>Save Changes</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}
