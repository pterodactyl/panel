import { Form, Formik } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import Field, { FieldRow } from '@/components/elements/Field';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SelectField from '@/components/elements/SelectField';
import { Context } from './SettingsRouter';
import { debounce } from 'debounce';

type Values = {
    appName: string;
    language: string;
};

export default function GeneralSettings() {
    const { name: appName, languages } = Context.useStoreState(state => state.settings!.general);
    const { locale: language } = useStoreState((state: ApplicationStore) => state.settings.data!);

    const submit = (values: Values) => {
        //
        console.log(values);
    };

    const setVariableValue = debounce((value: string) => {
        console.log(value);
    }, 500);

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                appName,
                language,
            }}
        >
            <Form>
                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6`}>
                    <AdminBox title="Branding">
                        <FieldRow>
                            <Field
                                onKeyUp={e => setVariableValue(e.currentTarget.value)}
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
                                options={Object.keys(languages).map(key => {
                                    return {
                                        value: key,
                                        label: languages[key as any] as unknown as string,
                                    };
                                })}
                            />
                        </FieldRow>
                    </AdminBox>
                </div>
            </Form>
        </Formik>
    );
}
