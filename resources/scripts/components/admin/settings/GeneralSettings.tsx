import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import Field, { FieldRow } from '@/components/elements/Field';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SelectField from '@/components/elements/SelectField';
import { Context } from './SettingsRouter';
import { Button } from '@/components/elements/button';
import { LanguageKey, updateSetting } from '@/api/admin/settings';
import { useStoreActions } from '@/state/hooks';
import { SiteSettings } from '@/state/settings';

type Values = {
    appName: string;
    language: LanguageKey;
};

export default function GeneralSettings() {
    const { name: appName, languages, language } = Context.useStoreState(state => state.settings!.general);
    const setSettings = useStoreActions(actions => actions.settings!.setSettings);

    const { addFlash, clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('admin:settings');
        setSubmitting(true);

        try {
            await updateSetting(values);
            setSettings({ name: values.appName, locale: values.language } as SiteSettings);
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
                appName,
                language,
            }}
        >
            {({ isSubmitting, isValid }) => (
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
                        <AdminBox title="Language">
                            <FieldRow>
                                <SelectField
                                    id={'language'}
                                    name={'language'}
                                    label={'Default language'}
                                    options={Object.keys(languages).map(lang => {
                                        return {
                                            value: lang,
                                            label: languages[lang as LanguageKey],
                                        };
                                    })}
                                />
                            </FieldRow>
                        </AdminBox>
                    </div>
                    <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mt-6`}>
                        <div css={tw`flex flex-row`}>
                            <Button type="submit" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
