import type { UserValues } from '@/api/admin/users/queries';
import type { AppForm } from '@/components/form';
import type { SelectOption } from '@/components/ui/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
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

const AccountFields = ({ form }: Pick<Props, 'form'>) => (
    <div className='space-y-6'>
        <form.AppField name='email' validators={{ onChange: validateUserEmail }}>
            {(field) => (
                <field.TextField
                    type='email'
                    id='email'
                    label='Email Address'
                    description='The email address this user will sign in and receive notifications with.'
                />
            )}
        </form.AppField>
        <form.AppField name='username' validators={{ onChange: validateUsername }}>
            {(field) => (
                <field.TextField
                    type='text'
                    id='username'
                    label='Username'
                    description='A unique username used to identify this account.'
                />
            )}
        </form.AppField>
        <div className='grid grid-cols-1 sm:grid-cols-2 gap-6'>
            <form.AppField name='nameFirst' validators={{ onChange: validateFirstName }}>
                {(field) => <field.TextField type='text' id='name_first' label='First Name' />}
            </form.AppField>
            <form.AppField name='nameLast' validators={{ onChange: validateLastName }}>
                {(field) => <field.TextField type='text' id='name_last' label='Last Name' />}
            </form.AppField>
        </div>
    </div>
);

const AuthenticationFields = ({ form, languageOptions, languagesLoading }: Props) => (
    <div className='space-y-6'>
        <form.AppField name='password' validators={{ onChange: validateUserPassword }}>
            {(field) => (
                <field.TextField
                    type='password'
                    id='password'
                    label='Password'
                    description='Leave blank to email this user a setup link to choose their own.'
                    autoComplete='new-password'
                />
            )}
        </form.AppField>
        <form.AppField
            name='language'
            validators={{
                onChange: ({ value }) => (value.length >= 1 ? undefined : 'A language must be provided.'),
            }}
        >
            {(field) => (
                <field.SelectField
                    id='language'
                    label='Language'
                    options={languageOptions}
                    disabled={languagesLoading}
                    placeholder='Select a language'
                    description='The default language for this account.'
                />
            )}
        </form.AppField>
    </div>
);

const AccessFields = ({ form }: Pick<Props, 'form'>) => (
    <form.AppField name='rootAdmin'>
        {(field) => (
            <field.SwitchField
                label='Administrator'
                description='Grant this account full administrative access to the Panel.'
            />
        )}
    </form.AppField>
);

export default function UserFormFields({ form, languageOptions, languagesLoading }: Props) {
    return (
        <div className='space-y-6'>
            <TitledGreyBox title='Account Details'>
                <AccountFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title='Authentication'>
                <AuthenticationFields
                    form={form}
                    languageOptions={languageOptions}
                    languagesLoading={languagesLoading}
                />
            </TitledGreyBox>
            <TitledGreyBox title='Access'>
                <AccessFields form={form} />
            </TitledGreyBox>
        </div>
    );
}
