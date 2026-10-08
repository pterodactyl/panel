import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { createAdminUserInput, type UserValues } from '@/api/admin/users/queries';
import { useAdminLanguages } from '@/api/admin/languages/queries';
import { useCreateAdminUser } from '@/api/admin/users/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import UserFormFields from '@/components/admin/users/UserFormFields';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, withExtensionPayload } from '@/extensions/forms';
import Icon from '@/components/elements/Icon';
import { useAppForm, Form } from '@/components/form';
import { languageOptions } from '@/components/admin/languageOptions';

const initialValues = (language: string): UserValues => ({
    email: '',
    username: '',
    nameFirst: '',
    nameLast: '',
    password: '',
    rootAdmin: false,
    language,
    extensions: initialExtensionValues(),
});

export default function CreateUserForm() {
    const navigate = useNavigate();
    const createUser = useCreateAdminUser();
    const { data: languages, isLoading: languagesLoading } = useAdminLanguages();
    const defaultLanguage = globalThis.document?.documentElement.lang.replace('-', '_') || 'en';
    const defaults = initialValues(defaultLanguage);
    const languagesList = languageOptions(languages, defaults.language);

    const form = useAppForm({
        defaultValues: defaults,
        onSubmit: async ({ value }) => {
            try {
                const user = await createUser.mutateAsync(
                    createAdminUserInput(withExtensionPayload('admin.user', value))
                );
                navigate({ to: '/panel/users/$id', params: { id: user.attributes.id } });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <AdminContentBlock
            title={'Admin · Create User'}
            heading={'Create User'}
            description={'Create a panel account and configure its access.'}
        >
            <Link
                to={'/panel/users'}
                className={'inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'}
            >
                <Icon icon={ArrowLeft} className={'mr-2'} />
                Back to Users
            </Link>
            <Form form={form}>
                <div className={'space-y-6'}>
                    <UserFormFields
                        form={form}
                        languageOptions={languagesList}
                        languagesLoading={languagesLoading && !languages}
                    />
                    <form.AppField name={'extensions'}>
                        {() => (
                            <ExtensionFormFields form={'admin.user'} mode={'create'} error={createUser.error} boxed />
                        )}
                    </form.AppField>
                    <div className={'flex justify-end'}>
                        <form.AppForm>
                            <form.SubmitButton>Create User</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </AdminContentBlock>
    );
}
