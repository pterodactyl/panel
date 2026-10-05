import { useRef } from 'react';
import { Link } from '@tanstack/react-router';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import { updateAdminEggScriptInput, useAdminEggs, useUpdateAdminEggScript } from '@/api/admin/eggs/queries';
import { useEggDetail } from '@/components/admin/eggs/useEggDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import type { CodemirrorEditorHandle } from '@/components/elements/LazyCodemirrorEditor';
import CodemirrorEditor from '@/components/elements/LazyCodemirrorEditor';

interface Props {
    egg: AdminEgg;
}

interface Values {
    scriptContainer: string;
    scriptEntry: string;
    scriptIsPrivileged: boolean;
    copyScriptFrom: number;
}

const eggToFormValues = (egg: AdminEgg): Values => ({
    scriptContainer: egg.attributes.script.container,
    scriptEntry: egg.attributes.script.entry,
    scriptIsPrivileged: egg.attributes.script.privileged ?? false,
    copyScriptFrom: egg.attributes.script.extends ?? 0,
});

function EggScriptForm({ egg }: Props) {
    const scriptEditor = useRef<CodemirrorEditorHandle | null>(null);

    const { data: eggsResponse } = useAdminEggs();
    const eggs = eggsResponse?.data ?? [];
    const updateEggScript = useUpdateAdminEggScript();

    const form = useAppForm({
        defaultValues: eggToFormValues(egg),
        onSubmit: async ({ value }) => {
            try {
                const scriptInstall = scriptEditor.current?.getValue() ?? egg.attributes.script.install;
                await updateEggScript.mutateAsync(
                    updateAdminEggScriptInput(egg.attributes.id, {
                        script_install: scriptInstall,
                        script_is_privileged: value.scriptIsPrivileged,
                        script_entry: value.scriptEntry,
                        script_container: value.scriptContainer,
                        copy_script_from: Number(value.copyScriptFrom) > 0 ? Number(value.copyScriptFrom) : null,
                    })
                );
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const copyScriptFrom = useStore(form.store, (state) => state.values.copyScriptFrom);
    const copyableEggs = eggs.filter(
        (option) => option.attributes.id !== egg.attributes.id && option.attributes.script.extends === null
    );
    const copiedFromEgg = eggs.find((option) => option.attributes.id === Number(copyScriptFrom));
    const dependentEggs = eggs.filter((option) => option.attributes.script.extends === egg.attributes.id);

    return (
        <Form form={form}>
            <TitledGreyBox title={'Install Script'}>
                {Number(copyScriptFrom) > 0 && (
                    <p className={'text-xs text-warning mb-4'}>
                        This egg is copying its install script
                        {copiedFromEgg ? (
                            <>
                                {' '}
                                from{' '}
                                <Link
                                    to={'/panel/eggs/$eggId/script'}
                                    params={{ eggId: copiedFromEgg.attributes.id }}
                                    className={'text-accent transition-colors duration-150 hover:text-accent/80'}
                                >
                                    {copiedFromEgg.attributes.name}
                                </Link>
                            </>
                        ) : (
                            ' from another egg'
                        )}
                        . Changes made to the script below will not apply unless you select &quot;None&quot; from the
                        &quot;Copy Script From&quot; dropdown.
                    </p>
                )}
                <CodemirrorEditor
                    ref={scriptEditor}
                    mode={'text/x-sh'}
                    initialContent={egg.attributes.script.install ?? undefined}
                    className={'h-96'}
                    onContentSaved={() => undefined}
                />
            </TitledGreyBox>
            <TitledGreyBox title={'Script Configuration'} className={'mt-6'}>
                <div className={'grid grid-cols-1 md:grid-cols-3 gap-6'}>
                    <form.AppField name={'copyScriptFrom'}>
                        {(field) => (
                            <field.SelectField
                                id={'copyScriptFrom'}
                                label={'Copy Script From'}
                                description={
                                    'If selected, the script above is ignored and the chosen egg’s script is used.'
                                }
                                options={[
                                    { value: 0, label: 'None' },
                                    ...copyableEggs.map((option) => ({
                                        value: option.attributes.id,
                                        label: option.attributes.name,
                                    })),
                                ]}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'scriptContainer'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'scriptContainer'}
                                label={'Script Container'}
                                description={'Docker container used when running this script for the server.'}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'scriptEntry'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'scriptEntry'}
                                label={'Script Entrypoint Command'}
                                description={'The entrypoint command to use for this script.'}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'mt-6'}>
                    <form.AppField name={'scriptIsPrivileged'}>
                        {(field) => (
                            <field.SwitchField
                                label={'Privileged Installation Script'}
                                description={
                                    'Run the installation script with elevated privileges, granting access to the ' +
                                    "node's docker socket. Only enable this when you trust the script."
                                }
                            />
                        )}
                    </form.AppField>
                </div>
            </TitledGreyBox>
            {dependentEggs.length > 0 && (
                <TitledGreyBox title={'Eggs Using This Script'} className={'mt-6'}>
                    <p className={'text-sm text-muted-foreground mb-4'}>
                        Changes to this install script affect these eggs because they copy from this one.
                    </p>
                    <div className={'space-y-2'}>
                        {dependentEggs.map((dependent) => (
                            <Link
                                key={dependent.attributes.id}
                                to={'/panel/eggs/$eggId/script'}
                                params={{ eggId: dependent.attributes.id }}
                                className={
                                    'block rounded-sm bg-muted px-3 py-2 text-sm text-foreground hover:text-accent'
                                }
                            >
                                {dependent.attributes.name}
                            </Link>
                        ))}
                    </div>
                </TitledGreyBox>
            )}
            <div className={'flex justify-end mt-6'}>
                <form.AppForm>
                    <form.SubmitButton>Save Changes</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

export default function EggScriptTab() {
    const egg = useEggDetail();

    return <EggScriptForm key={egg.attributes.id} egg={egg} />;
}
