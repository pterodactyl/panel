import { useRef } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { useAppForm, Form } from '@/components/form';
import { httpErrorToHuman } from '@/api/http';
import { createAdminEggInput, useAdminEggs, useCreateAdminEgg } from '@/api/admin/eggs/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { useExtensionPayload } from '@/extensions/forms';
import Icon from '@/components/elements/Icon';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import EggConfigurationFields from '@/components/admin/eggs/EggConfigurationForm';
import { emptyEggFormValues, toApiValues } from '@/components/admin/eggs/helpers';
import type { CodemirrorEditorHandle } from '@/components/elements/LazyCodemirrorEditor';

export default function CreateEggForm() {
    const navigate = useNavigate();

    const logsEditor = useRef<CodemirrorEditorHandle | null>(null);
    const filesEditor = useRef<CodemirrorEditorHandle | null>(null);
    const startupEditor = useRef<CodemirrorEditorHandle | null>(null);

    const { data: eggs, isFetching: eggsFetching, error: eggsError } = useAdminEggs();
    const createEgg = useCreateAdminEgg();

    const { withExtensionPayload } = useExtensionPayload('admin.egg');
    const form = useAppForm({
        defaultValues: emptyEggFormValues(),
        onSubmit: async ({ value }) => {
            try {
                const configLogs = logsEditor.current?.getValue() ?? '';
                const configFiles = filesEditor.current?.getValue() ?? '';
                const configStartup = startupEditor.current?.getValue() ?? '';
                const egg = await createEgg.mutateAsync(
                    createAdminEggInput(
                        toApiValues(withExtensionPayload(value), {
                            configLogs,
                            configFiles,
                            configStartup,
                        })
                    )
                );

                void navigate({
                    to: '/panel/eggs/$eggId',
                    params: { eggId: egg.attributes.id },
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    if (eggsError) {
        return <ServerError message={httpErrorToHuman(eggsError)} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Create Egg'
            heading='Create Egg'
            description='Create a reusable server template and startup configuration.'
        >
            <Link
                to='/panel/eggs'
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                Back to Eggs
            </Link>
            {eggsFetching ? (
                <Spinner size='large' centered />
            ) : (
                <Form form={form}>
                    <EggConfigurationFields
                        form={form}
                        eggs={eggs?.data ?? []}
                        initialConfigLogs=''
                        initialConfigFiles=''
                        initialConfigStartup=''
                        logRef={logsEditor}
                        filesRef={filesEditor}
                        startupRef={startupEditor}
                    />
                    <form.AppField name='extensions'>
                        {() => (
                            <ExtensionFormFields
                                form='admin.egg'
                                mode='create'
                                error={createEgg.error}
                                boxed
                                className='mt-4'
                            />
                        )}
                    </form.AppField>
                    <div className='flex justify-end mt-6'>
                        <form.AppForm>
                            <form.SubmitButton>Create Egg</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </Form>
            )}
        </AdminContentBlock>
    );
}
