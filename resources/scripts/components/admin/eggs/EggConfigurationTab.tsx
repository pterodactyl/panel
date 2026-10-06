import { useRef } from 'react';
import { useAppForm, Form } from '@/components/form';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import { updateAdminEggInput, useAdminEggs, useUpdateAdminEgg } from '@/api/admin/eggs/queries';
import { useEggDetail } from '@/components/admin/eggs/useEggDetail';
import EggConfigurationFields from '@/components/admin/eggs/EggConfigurationForm';
import { eggToFormValues, toApiValues } from '@/components/admin/eggs/helpers';
import type { CodemirrorEditorHandle } from '@/components/elements/LazyCodemirrorEditor';
import Spinner from '@/components/elements/Spinner';
import Slot from '@/extensions/Slot';
import type { LoadedExtensionFieldValues } from '@/extensions/formFields';
import { useExtensionFormFields } from '@/extensions/useExtensionFormFields';

interface Props {
    egg: AdminEgg;
    extensions: LoadedExtensionFieldValues;
    hidden: readonly string[];
}

function EggConfigurationForm({ egg, extensions, hidden }: Props) {
    const logsEditor = useRef<CodemirrorEditorHandle | null>(null);
    const filesEditor = useRef<CodemirrorEditorHandle | null>(null);
    const startupEditor = useRef<CodemirrorEditorHandle | null>(null);

    const { data: eggOptions } = useAdminEggs();
    const eggs = (eggOptions?.data ?? []).filter((option) => option.attributes.id !== egg.attributes.id);
    const updateEgg = useUpdateAdminEgg();

    const initialValues = eggToFormValues(egg, extensions);

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
            <Slot
                name={'panel.eggs.detail.configuration.form'}
                data={{ kind: 'admin.egg', mode: 'edit', resource: egg, form }}
                hidden={hidden}
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
    const extensions = useExtensionFormFields('admin.egg', egg.attributes.id);

    if (!extensions.ready) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <EggConfigurationForm
            key={egg.attributes.id}
            egg={egg}
            extensions={extensions.values}
            hidden={extensions.hidden}
        />
    );
}
