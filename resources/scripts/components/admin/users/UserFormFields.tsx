import type { UserValues } from '@/api/admin/users/queries';
import type { AppForm } from '@/components/form';
import type { SelectOption } from '@/components/ui/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useTranslation } from '@/sdk/localization';
import {
    validateFirstName,
    validateLastName,
    validateUserEmail,
    validateUsername,
    validateUserPassword,
} from '@/components/admin/users/userForm';

interface Props {
    form: AppForm<UserValues>;
    languageOptions: SelectOption[];
    languagesLoading: boolean;
}

const AccountFields = ({ form }: Pick<Props, 'form'>) => {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <form.AppField name='email' validators={{ onChange: validateUserEmail }}>
                {(field) => (
                    <field.TextField
                        type='email'
                        id='email'
                        label={t('Email Address')}
                        description={t('The email address this user will sign in and receive notifications with.')}
                    />
                )}
            </form.AppField>
            <form.AppField name='username' validators={{ onChange: validateUsername }}>
                {(field) => (
                    <field.TextField
                        type='text'
                        id='username'
                        label={t('Username')}
                        description={t('A unique username used to identify this account.')}
                    />
                )}
            </form.AppField>
            <div className='grid grid-cols-1 sm:grid-cols-2 gap-6'>
                <form.AppField name='nameFirst' validators={{ onChange: validateFirstName }}>
                    {(field) => <field.TextField type='text' id='name_first' label={t('First Name')} />}
                </form.AppField>
                <form.AppField name='nameLast' validators={{ onChange: validateLastName }}>
                    {(field) => <field.TextField type='text' id='name_last' label={t('Last Name')} />}
                </form.AppField>
            </div>
        </div>
    );
};

const AuthenticationFields = ({ form, languageOptions, languagesLoading }: Props) => {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <form.AppField name='password' validators={{ onChange: validateUserPassword }}>
                {(field) => (
                    <field.TextField
                        type='password'
                        id='password'
                        label={t('Password')}
                        description={t('Leave blank to email this user a setup link to choose their own.')}
                        autoComplete='new-password'
                    />
                )}
            </form.AppField>
            <form.AppField name='language'>
                {(field) => (
                    <field.SelectField
                        id='language'
                        label={t('Language')}
                        options={languageOptions}
                        disabled={languagesLoading}
                        placeholder={t('Select a language')}
                        description={t('The default language for this account.')}
                    />
                )}
            </form.AppField>
        </div>
    );
};

const AccessFields = ({ form }: Pick<Props, 'form'>) => {
    const { t } = useTranslation();

    return (
        <form.AppField name='rootAdmin'>
            {(field) => (
                <field.SwitchField
                    label={t('Administrator')}
                    description={t('Grant this account full administrative access to the Panel.')}
                />
            )}
        </form.AppField>
    );
};

export default function UserFormFields({ form, languageOptions, languagesLoading }: Props) {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <TitledGreyBox title={t('Account Details')}>
                <AccountFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Authentication')}>
                <AuthenticationFields
                    form={form}
                    languageOptions={languageOptions}
                    languagesLoading={languagesLoading}
                />
            </TitledGreyBox>
            <TitledGreyBox title={t('Access')}>
                <AccessFields form={form} />
            </TitledGreyBox>
        </div>
    );
}
