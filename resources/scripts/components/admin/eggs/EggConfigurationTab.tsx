import { useRef } from 'react';
import { useAppForm, Form } from '@/components/form';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import { updateAdminEggInput, useAdminEggs, useUpdateAdminEgg } from '@/api/admin/eggs/queries';
import { useEggDetail } from '@/components/admin/eggs/useEggDetail';
import EggConfigurationFields from '@/components/admin/eggs/EggConfigurationForm';
import { eggToFormValues, toApiValues } from '@/components/admin/eggs/helpers';
import type { CodemirrorEditorHandle } from '@/components/elements/LazyCodemirrorEditor';

interface Props {
    egg: AdminEgg;
}

function EggConfigurationForm({ egg }: Props) {
    const logsEditor = useRef<CodemirrorEditorHandle | null>(null);
    const filesEditor = useRef<CodemirrorEditorHandle | null>(null);
    const startupEditor = useRef<CodemirrorEditorHandle | null>(null);

    const { data: eggOptions } = useAdminEggs();
    const eggs = (eggOptions?.data ?? []).filter((option) => option.attributes.id !== egg.attributes.id);
    const updateEgg = useUpdateAdminEgg();

    const initialValues = eggToFormValues(egg);

    const form = useAppForm({
        defaultValues: initialValues,
        onSubmit: async ({ value }) => {
            try {
                const configLogs = logsEditor.current?.getValue() ?? initialValues.configLogs;
                const configFiles = filesEditor.current?.getValue() ?? initialValues.configFiles;
                const configStartup = startupEditor.current?.getValue() ?? initialValues.configStartup;
                await updateEgg.mutateAsync(
                    updateAdminEggInput(
                        egg.attributes.id,
                        toApiValues(value, { configLogs, configFiles, configStartup })
                    )
                );
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <Form form={form}>
            <EggConfigurationFields
                form={form}
                eggs={eggs}
                initialConfigLogs={initialValues.configLogs}
                initialConfigFiles={initialValues.configFiles}
                initialConfigStartup={initialValues.configStartup}
                logRef={logsEditor}
                filesRef={filesEditor}
                startupRef={startupEditor}
            />
            <div className={'flex justify-end mt-6'}>
                <form.AppForm>
                    <form.SubmitButton>Save Changes</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

export default function EggConfigurationTab() {
    const egg = useEggDetail();

    return <EggConfigurationForm key={egg.attributes.id} egg={egg} />;
}
