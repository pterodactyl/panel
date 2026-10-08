import type { AdminUser, UserValues } from '@/api/admin/users/queries';
import type { AppForm } from '@/components/form';
import type { SelectOption } from '@/components/ui/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

export const userFormValues = (user: AdminUser): UserValues => ({
    email: user.attributes.email,
    username: user.attributes.username,
    nameFirst: user.attributes.first_name ?? '',
    nameLast: user.attributes.last_name ?? '',
    password: '',
    rootAdmin: user.attributes.root_admin,
    language: user.attributes.language,
});

export const validateUserPassword = ({ value }: { value: string }): string | undefined => {
    if (value.length === 0) {
        return undefined;
    }

    if (value.length < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (value.length > 191) {
        return 'Password must not exceed 191 characters.';
    }

    return undefined;
};

export const validateUserEmail = ({ value }: { value: string }): string | undefined => {
    if (value.length < 1) {
        return 'An email address must be provided.';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return 'A valid email address must be provided.';
    }

    return undefined;
};

const requiredWithMaxLength =
    (missing: string, tooLong: string) =>
    ({ value }: { value: string }): string | undefined => {
        if (value.length < 1) {
            return missing;
        }

        if (value.length > 191) {
            return tooLong;
        }

        return undefined;
    };

export const validateUsername = requiredWithMaxLength(
    'A username must be provided.',
    'A username must not exceed 191 characters.'
);

export const validateFirstName = requiredWithMaxLength(
    'A first name must be provided.',
    'First name must not exceed 191 characters.'
);

export const validateLastName = requiredWithMaxLength(
    'A last name must be provided.',
    'Last name must not exceed 191 characters.'
);

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
