import React from 'react';
import type { AppForm } from '@/components/form';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Label from '@/components/elements/Label';
import type { CodemirrorEditorHandle } from '@/components/elements/LazyCodemirrorEditor';
import CodemirrorEditor from '@/components/elements/LazyCodemirrorEditor';
import type { AdminEggListItem } from '@/api/admin/eggs/queries';
import type { EggFormValues } from '@/components/admin/eggs/helpers';

export type EggForm = AppForm<EggFormValues>;

const validateEggName = (value: string): string | undefined => {
    if (value.length < 1) {
        return 'An egg name must be provided.';
    }

    if (value.length > 191) {
        return 'An egg name must not exceed 191 characters.';
    }

    return undefined;
};

const EggJsonField = ({
    label,
    description,
    initialContent,
    editorRef,
}: {
    label: string;
    description: string;
    initialContent: string;
    editorRef: React.RefObject<CodemirrorEditorHandle | null>;
}) => (
    <div>
        <Label>{label}</Label>
        <CodemirrorEditor
            ref={editorRef}
            mode='application/json'
            initialContent={initialContent}
            className='h-72'
            onContentSaved={() => {}}
        />
        <p className='input-help'>{description}</p>
    </div>
);

interface Props {
    form: EggForm;
    eggs: AdminEggListItem[];
    initialConfigLogs: string;
    initialConfigFiles: string;
    initialConfigStartup: string;
    logRef: React.RefObject<CodemirrorEditorHandle | null>;
    filesRef: React.RefObject<CodemirrorEditorHandle | null>;
    startupRef: React.RefObject<CodemirrorEditorHandle | null>;
}

const EggConfigurationFields = ({
    form,
    eggs,
    initialConfigLogs,
    initialConfigFiles,
    initialConfigStartup,
    logRef,
    filesRef,
    startupRef,
}: Props) => (
    <div className='space-y-4'>
        <TitledGreyBox title='Configuration'>
            <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
                <div className='space-y-4'>
                    <form.AppField
                        name='name'
                        validators={{
                            onChange: ({ value }) => validateEggName(value),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='name'
                                label='Name'
                                description={
                                    'A simple, human-readable name to use as an identifier for this Egg. This is what ' +
                                    'users will see as their game server type.'
                                }
                            />
                        )}
                    </form.AppField>
                    <form.AppField name='description'>
                        {(field) => (
                            <field.TextAreaField
                                id='description'
                                label='Description'
                                rows={6}
                                description='A description of this Egg.'
                            />
                        )}
                    </form.AppField>
                    <form.AppField name='forceOutgoingIp'>
                        {(field) => (
                            <field.SwitchField
                                label='Force Outgoing IP'
                                description={
                                    'Forces all outgoing network traffic to have its Source IP NATed to the IP of the ' +
                                    "server's primary allocation IP. Enabling this disables internal networking for " +
                                    'servers using this egg.'
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='space-y-4'>
                    <form.AppField
                        name='dockerImages'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'At least one docker image must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextAreaField
                                id='dockerImages'
                                label='Docker Images'
                                rows={4}
                                placeholder='ghcr.io/pterodactyl/yolks:debian'
                                description={
                                    'The docker images available to servers using this egg. Enter one per line. ' +
                                    'Optionally provide a display name by prefixing the image with the name and a ' +
                                    'pipe, e.g. Display Name|ghcr.io/my/egg.'
                                }
                            />
                        )}
                    </form.AppField>
                    <form.AppField
                        name='startup'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'A startup command must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextAreaField
                                id='startup'
                                label='Startup Command'
                                rows={8}
                                description='The default startup command used for new servers created with this Egg.'
                            />
                        )}
                    </form.AppField>
                    <form.AppField name='featuresText'>
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='featuresText'
                                label='Features'
                                description={
                                    'Additional features belonging to the egg, comma-separated. Useful for configuring ' +
                                    'additional panel modifications.'
                                }
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
        </TitledGreyBox>

        <TitledGreyBox title='Process Management'>
            <p className='text-xs text-warning mb-4'>
                These options should not be edited unless you understand how this system works. All fields are required
                unless you select an Egg from the &quot;Copy Settings From&quot; dropdown, in which case the process
                fields may be left blank to inherit from that Egg.
            </p>
            <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
                <form.AppField name='configFrom'>
                    {(field) => (
                        <field.SelectField
                            id='configFrom'
                            label='Copy Settings From'
                            description='Default to the process settings from another Egg.'
                            options={[
                                { value: 0, label: 'None' },
                                ...eggs.map((egg) => ({
                                    value: egg.attributes.id,
                                    label: `${egg.attributes.name} <${egg.attributes.author}>`,
                                })),
                            ]}
                        />
                    )}
                </form.AppField>
                <form.AppField name='configStop'>
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='configStop'
                            label='Stop Command'
                            description='The command sent to server processes to stop them gracefully. To send a SIGINT enter ^C.'
                        />
                    )}
                </form.AppField>
            </div>
            <div className='grid grid-cols-1 md:grid-cols-2 gap-4 mt-4'>
                <EggJsonField
                    label='Log Configuration'
                    description={
                        'A JSON representation of where log files are stored and whether the daemon should ' +
                        'create custom logs. Leave blank to inherit this block from the selected parent egg.'
                    }
                    initialContent={initialConfigLogs}
                    editorRef={logRef}
                />
                <EggJsonField
                    label='Configuration Files'
                    description={
                        'A JSON representation of configuration files to modify and what parts should be changed. ' +
                        'Leave blank to inherit this block from the selected parent egg.'
                    }
                    initialContent={initialConfigFiles}
                    editorRef={filesRef}
                />
                <EggJsonField
                    label='Start Configuration'
                    description={
                        'A JSON representation of the values the daemon should look for when booting a server ' +
                        'to determine completion. Leave blank to inherit this block from the selected parent egg.'
                    }
                    initialContent={initialConfigStartup}
                    editorRef={startupRef}
                />
            </div>
        </TitledGreyBox>
    </div>
);

export default EggConfigurationFields;
