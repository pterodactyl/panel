import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { createAdminMountInput, type MountValues, useCreateAdminMount } from '@/api/admin/mounts/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import MountFormFields from '@/components/admin/mounts/MountFormFields';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, withExtensionPayload } from '@/extensions/forms';
import Icon from '@/components/elements/Icon';
import { useAppForm, Form } from '@/components/form';

const initialValues = (): MountValues => ({
    name: '',
    description: '',
    source: '',
    target: '',
    readOnly: false,
    userMountable: false,
    extensions: initialExtensionValues(),
});

export default function CreateMountForm() {
    const navigate = useNavigate();
    const createMount = useCreateAdminMount();

    const form = useAppForm({
        defaultValues: initialValues(),
        onSubmit: async ({ value }) => {
            try {
                const mount = await createMount.mutateAsync(
                    createAdminMountInput(withExtensionPayload('admin.mount', value))
                );
                navigate({ to: '/panel/mounts/$id', params: { id: mount.attributes.id } });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <AdminContentBlock
            title={'Admin · Create Mount'}
            heading={'Create Mount'}
            description={'Create a directory mount that can be attached to servers.'}
        >
            <Link
                to={'/panel/mounts'}
                className={'inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'}
            >
                <Icon icon={ArrowLeft} className={'mr-2'} />
                Back to Mounts
            </Link>
            <Form form={form}>
                <div className={'space-y-6'}>
                    <MountFormFields form={form} />
                    <form.AppField name={'extensions'}>
                        {() => (
                            <ExtensionFormFields form={'admin.mount'} mode={'create'} error={createMount.error} boxed />
                        )}
                    </form.AppField>
                    <div className={'flex justify-end'}>
                        <form.AppForm>
                            <form.SubmitButton>Create Mount</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </AdminContentBlock>
    );
}
