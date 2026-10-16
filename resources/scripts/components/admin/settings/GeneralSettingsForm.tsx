import { useState } from 'react';
import { useAppForm, Form } from '@/components/form';
import type { AdminSettings } from '@/api/admin/settings/queries';
import { useAdminLanguages } from '@/api/admin/languages/queries';
import {
    updateAdminGeneralSettingsInput,
    useClearAdminLogo,
    useUpdateAdminGeneralSettings,
    useUploadAdminLogo,
} from '@/api/admin/settings/queries';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { languageOptions } from '@/components/admin/languageOptions';
import LogoSettingsForm from '@/components/admin/settings/LogoSettingsForm';

export default function GeneralSettingsForm({ settings }: { settings: AdminSettings }) {
    const readOnly = settings.meta.load_environment_only;
    const updateGeneralSettings = useUpdateAdminGeneralSettings();
    const uploadLogo = useUploadAdminLogo();
    const clearLogo = useClearAdminLogo();
    const [pendingLogo, setPendingLogo] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const { data: languages, isLoading: languagesLoading } = useAdminLanguages();
    const languagesList = languageOptions(languages, settings.general['app:locale']);

    const form = useAppForm({
        defaultValues: {
            name: settings.general['app:name'],
            twoFactorRequired: settings.general['pterodactyl:auth:2fa_required'],
            locale: settings.general['app:locale'],
        },
        onSubmit: async ({ value }) => {
            if (readOnly) {
                return;
            }

            try {
                await updateGeneralSettings.mutateAsync(updateAdminGeneralSettingsInput(value));

                if (pendingLogo) {
                    await uploadLogo.mutateAsync(pendingLogo);
                    setPendingLogo(null);
                    setRemoveLogo(false);
                } else if (removeLogo) {
                    await clearLogo.mutateAsync();
                    setRemoveLogo(false);
                }
            } catch {
                // Error toasts are handled by the mutations.
            }
        },
    });

    return (
        <Form form={form}>
            <TitledGreyBox title='General'>
                <form.AppField name='name'>
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='app_name'
                            label='Company Name'
                            description='The name of your company, used throughout the Panel.'
                            disabled={readOnly}
                        />
                    )}
                </form.AppField>
                <LogoSettingsForm
                    settings={settings}
                    pendingFile={pendingLogo}
                    removePending={removeLogo}
                    disabled={uploadLogo.isPending || clearLogo.isPending}
                    onFileSelected={(file) => {
                        setPendingLogo(file ?? null);
                        setRemoveLogo(false);
                    }}
                    onRemove={() => {
                        setPendingLogo(null);
                        setRemoveLogo(true);
                    }}
                />
                <div className='mt-4'>
                    <form.AppField name='twoFactorRequired'>
                        {(field) => (
                            <field.SelectField
                                id='twoFactorRequired'
                                label='Require 2-Factor Authentication'
                                options={[
                                    { value: 0, label: 'Not Required' },
                                    { value: 1, label: 'Required for Admins' },
                                    { value: 2, label: 'Required for All Users' },
                                ]}
                                disabled={readOnly}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-4'>
                    <form.AppField name='locale'>
                        {(field) => (
                            <field.SelectField
                                id='app_locale'
                                label='Default Language'
                                options={languagesList}
                                disabled={readOnly || (languagesLoading && !languages)}
                                placeholder='Select a language'
                                description='The default language used for the Panel.'
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='flex justify-end mt-4'>
                    <form.AppForm>
                        <form.SubmitButton
                            disabled={readOnly || updateGeneralSettings.isPending || uploadLogo.isPending || clearLogo.isPending}
                        >
                            Save Changes
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </TitledGreyBox>
        </Form>
    );
}
