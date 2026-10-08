import React, { useState } from 'react';
import { AlertCircle, FileArchive, RefreshCw, Settings2, Trash2, UploadCloud } from 'lucide-react';
import { getExtensionStates, useExtensionRegistry } from '@/extensions/registry';
import { cn } from '@/lib/cn';
import { httpErrorToHuman } from '@/api/http';
import {
    disableAdminExtensionInput,
    enableAdminExtensionInput,
    extensionReplacement,
    installAdminExtensionInput,
    removeAdminExtensionInput,
    updateAdminExtensionSettingsInput,
    type AdminExtension,
    type AdminExtensionReplacement,
    type AdminExtensionSettingField,
    type AdminExtensionSettingValue,
    useAdminExtensions,
    useAdminExtensionSettings,
    useDisableAdminExtension,
    useEnableAdminExtension,
    useInstallAdminExtension,
    useRemoveAdminExtension,
    uploadAdminExtensionSettingFileInput,
    clearAdminExtensionSettingFileInput,
    useClearAdminExtensionSettingFile,
    useUploadAdminExtensionSettingFile,
    useUpdateAdminExtensionSettings,
} from '@/api/admin/extensions/queries';
import SettingField, { submittedItems, type SettingFileActions } from './SettingField';
import SettingsSections, { settingsBodyClass } from './SettingsSections';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Button from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import Label from '@/components/elements/Label';
import NamedIcon from '@/components/elements/NamedIcon';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewButton } from '@/components/elements/NewButton';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Alert } from '@/components/elements/alert';
import { Dialog } from '@/components/elements/dialog';
import { ServerError } from '@/components/elements/ScreenBlock';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Checkbox from '@/components/ui/Checkbox';
import Switch from '@/components/ui/Switch';
import { FileInput } from '@/components/form/controls';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

type ExtensionState = NonNullable<AdminExtension['state']>;

type ActionButtonProps = {
    label: string;
    icon: React.ComponentProps<typeof Icon>['icon'];
    disabled?: boolean;
    color?: React.ComponentProps<typeof Button>['color'];
    isLoading?: boolean;
    onClick: () => void;
};

const stateBadge = (state: ExtensionState = '') => {
    switch (state) {
        case 'not_registered':
            return { label: 'Not set up', className: 'bg-accent/10 text-accent' };
        case 'error':
            return { label: 'Failed to load', className: 'bg-destructive/15 text-destructive' };
        case 'invalid':
            return { label: 'Invalid package', className: 'bg-destructive/15 text-destructive' };
        default:
            return null;
    }
};

const extensionName = (extension: AdminExtension): string => extension.name || extension.id || 'Unknown extension';

const extensionId = (extension: AdminExtension): string => extension.id || extensionName(extension);

const canRemove = (extension: AdminExtension): boolean => extension.state !== 'invalid' && !!extension.id;

const canEnable = (extension: AdminExtension): boolean =>
    extension.state !== 'invalid' && !extension.enabled && !!extension.id;

const canDisable = (extension: AdminExtension): boolean => extension.enabled === true && !!extension.id;

const extensionInitials = (extension: AdminExtension): string =>
    extensionName(extension)
        .split(/[\s_-]+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || 'EX';

function ExtensionMarkContent({ extension }: { extension: AdminExtension }) {
    const [failedUrl, setFailedUrl] = useState<string | null>(null);
    const iconUrl = extension.icon_url;

    if (iconUrl && iconUrl !== failedUrl) {
        return (
            <img
                src={iconUrl}
                alt=''
                loading='lazy'
                decoding='async'
                className='h-full w-full object-contain'
                onError={() => setFailedUrl(iconUrl)}
            />
        );
    }

    if (extension.icon) {
        return <NamedIcon name={extension.icon} size={20} />;
    }

    return <>{extensionInitials(extension)}</>;
}

export function ExtensionMark({ extension }: { extension: AdminExtension }) {
    return (
        <div
            className='flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-secondary text-sm font-semibold text-secondary-foreground'
            aria-hidden='true'
        >
            <ExtensionMarkContent extension={extension} />
        </div>
    );
}

function ActionButton({ label, icon, disabled, color = 'grey', isLoading, onClick }: ActionButtonProps) {
    return (
        <NewButton
            icon={icon}
            color={color}
            isSecondary
            disabled={disabled}
            isLoading={isLoading}
            className='h-8 px-2.5 text-sm'
            onClick={onClick}
        >
            {label}
        </NewButton>
    );
}

function ReplacementNotice({ replacement }: { replacement: AdminExtensionReplacement }) {
    const installed = replacement.installedVersion ? `v${replacement.installedVersion}` : 'a copy';

    return (
        <Alert type='warning' title='Replace an installed extension?'>
            This package is <code className='font-mono'>{replacement.id}</code> v{replacement.version}, and {installed}{' '}
            of <code className='font-mono'>{replacement.id}</code> is already installed. Installing it replaces the
            installed files.
            {replacement.enabled &&
                ' The extension is enabled and stays enabled, so the new version runs its migrations and code right away.'}
        </Alert>
    );
}

function InstallExtensionDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [file, setFile] = useState<File | null>(null);
    const [enable, setEnable] = useState(false);
    const [replacement, setReplacement] = useState<AdminExtensionReplacement | null>(null);
    const installExtension = useInstallAdminExtension();
    const submitting = installExtension.isPending;

    const submit = async () => {
        if (!file) {
            return;
        }

        try {
            await installExtension.mutateAsync(installAdminExtensionInput(file, enable, replacement !== null));
            onClose();
        } catch (error) {
            // A package with an installed id waits for the admin to confirm; other errors are toasted by the mutation.
            const conflict = extensionReplacement(error);

            if (conflict) {
                setReplacement(conflict);
            }
        }
    };

    return (
        <Dialog
            open={open}
            title='Install extension'
            preventExternalClose={submitting}
            hideCloseIcon={submitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={submitting} />
            <div className='space-y-5'>
                <div>
                    <Label htmlFor='extension_package'>Package</Label>
                    <FileInput
                        id='extension_package'
                        accept='.pteroext,.zip,application/zip'
                        aria-label='Extension package'
                        disabled={submitting}
                        onChange={(event) => {
                            setFile(event.currentTarget.files?.[0] ?? null);
                            setReplacement(null);
                        }}
                    />
                </div>
                <label className='flex items-center gap-3 text-sm text-foreground'>
                    <Checkbox checked={enable} onChange={setEnable} disabled={submitting} />
                    Enable after install
                </label>
                {replacement && <ReplacementNotice replacement={replacement} />}
            </div>
            <div className='flex flex-wrap justify-end mt-6'>
                <Button
                    type='button'
                    isSecondary
                    className='w-full sm:w-auto sm:mr-2'
                    disabled={submitting}
                    onClick={onClose}
                >
                    Cancel
                </Button>
                <Button
                    type='button'
                    color={replacement ? 'red' : 'primary'}
                    className='w-full mt-4 sm:w-auto sm:mt-0'
                    disabled={!file || submitting}
                    isLoading={submitting}
                    onClick={() => void submit()}
                >
                    {replacement ? `Replace ${replacement.id}` : 'Install'}
                </Button>
            </div>
        </Dialog>
    );
}

function InstallExtensionButton() {
    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <NewButton icon={UploadCloud} onClick={onClick}>
                    Install
                </NewButton>
            )}
        >
            {({ open, onClose }) => <InstallExtensionDialog open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}

function ExtensionToolbar({
    failedCount,
    isFetching,
    onRefresh,
}: {
    failedCount: number;
    isFetching: boolean;
    onRefresh: () => void;
}) {
    return (
        <ListToolbar
            summary={
                failedCount > 0 && (
                    <span className='inline-flex items-center gap-1.5 text-warning'>
                        <Icon icon={AlertCircle} />
                        {failedCount === 1 ? '1 extension needs attention' : `${failedCount} extensions need attention`}
                    </span>
                )
            }
        >
            <div className='flex items-center gap-2'>
                <Tooltip content='Refresh'>
                    <span className='inline-flex'>
                        <Button
                            type='button'
                            color='grey'
                            isSecondary
                            aria-label='Refresh extensions'
                            disabled={isFetching}
                            isLoading={isFetching}
                            className='inline-flex h-9 w-9 items-center justify-center p-0'
                            onClick={onRefresh}
                        >
                            <Icon icon={RefreshCw} />
                        </Button>
                    </span>
                </Tooltip>
                <InstallExtensionButton />
            </div>
        </ListToolbar>
    );
}

const resolveSettingValue = (
    values: Record<string, AdminExtensionSettingValue | undefined>,
    field: AdminExtensionSettingField
): AdminExtensionSettingValue | undefined => {
    if (field.input in values) {
        return values[field.input];
    }

    // Secrets arrive masked; blank means "keep the stored value".
    return field.field === 'password' ? '' : field.value;
};

const shouldIncludeSettingValue = (
    field: AdminExtensionSettingField,
    value: AdminExtensionSettingValue | undefined
): value is AdminExtensionSettingValue => {
    // Files are saved through their own upload endpoint.
    if (value === undefined || field.field === 'file') {
        return false;
    }

    return !(field.field === 'password' && value === '');
};

const buildSettingsPayload = (
    schema: AdminExtensionSettingField[],
    getValue: (field: AdminExtensionSettingField) => AdminExtensionSettingValue | undefined
) => {
    const settings: Record<string, AdminExtensionSettingValue> = {};

    for (const field of schema) {
        const value = getValue(field);

        if (shouldIncludeSettingValue(field, value)) {
            settings[field.input] = field.field === 'list' ? submittedItems(value) : value;
        }
    }

    return settings;
};

function SettingsEmptyState({ extensionEnabled }: { extensionEnabled: boolean | undefined }) {
    return (
        <Empty className={emptyCompactClass}>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Settings2 />
                </EmptyMedia>
                <EmptyTitle>{extensionEnabled ? 'No settings' : 'Settings unavailable'}</EmptyTitle>
                <EmptyDescription>
                    {extensionEnabled
                        ? "This extension doesn't register any settings."
                        : 'Enable the extension to load its settings.'}
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function SettingsDialogContent({
    error,
    showLoading,
    registered,
    schema,
    extensionEnabled,
    getValue,
    onChange,
    fileActions,
}: {
    error: unknown;
    showLoading: boolean;
    registered: boolean;
    schema: AdminExtensionSettingField[];
    extensionEnabled: boolean | undefined;
    getValue: (field: AdminExtensionSettingField) => AdminExtensionSettingValue | undefined;
    onChange: (field: AdminExtensionSettingField) => (value: AdminExtensionSettingValue) => void;
    fileActions: (field: AdminExtensionSettingField) => SettingFileActions;
}) {
    if (error) {
        return (
            <div className={settingsBodyClass}>
                <Alert type='danger'>
                    <span>{httpErrorToHuman(error)}</span>
                </Alert>
            </div>
        );
    }

    if (showLoading) {
        return (
            <div className={cn(settingsBodyClass, 'justify-center')}>
                <Spinner size='large' centered />
            </div>
        );
    }

    if (!registered || schema.length === 0) {
        return (
            <div className={cn(settingsBodyClass, 'justify-center')}>
                <SettingsEmptyState extensionEnabled={extensionEnabled} />
            </div>
        );
    }

    return (
        <SettingsSections
            schema={schema}
            renderField={(field) => (
                <SettingField
                    key={field.input}
                    field={field}
                    value={getValue(field)}
                    onChange={onChange(field)}
                    file={field.field === 'file' ? fileActions(field) : undefined}
                />
            )}
        />
    );
}

function SettingsDialogActions({
    submitting,
    canSave,
    onCancel,
    onSubmit,
}: {
    submitting: boolean;
    canSave: boolean;
    onCancel: () => void;
    onSubmit: () => void;
}) {
    return (
        <div className='flex flex-wrap justify-end mt-6'>
            <Button
                type='button'
                isSecondary
                className='w-full sm:w-auto sm:mr-2'
                disabled={submitting}
                onClick={onCancel}
            >
                Cancel
            </Button>
            <Button
                type='button'
                className='w-full mt-4 sm:w-auto sm:mt-0'
                disabled={submitting || !canSave}
                isLoading={submitting}
                onClick={onSubmit}
            >
                Save Settings
            </Button>
        </div>
    );
}

function SettingsDialog({
    extension,
    open,
    onClose,
}: {
    extension: AdminExtension;
    open: boolean;
    onClose: () => void;
}) {
    const id = extension.id ?? '';
    const { data, error, isFetching } = useAdminExtensionSettings(id, { enabled: open && id.length > 0 });
    const updateSettings = useUpdateAdminExtensionSettings();
    const uploadFile = useUploadAdminExtensionSettingFile();
    const clearFile = useClearAdminExtensionSettingFile();
    const [values, setValues] = useState<Record<string, AdminExtensionSettingValue | undefined>>({});
    const submitting = updateSettings.isPending;

    const registered = data?.data?.registered ?? false;
    const schema = data?.data.schema ?? [];
    const showLoading = isFetching && !data;

    const getValue = (field: AdminExtensionSettingField): AdminExtensionSettingValue | undefined =>
        resolveSettingValue(values, field);

    const handleChange = (field: AdminExtensionSettingField) => (value: AdminExtensionSettingValue) =>
        setValues((current) => ({ ...current, [field.input]: value }));

    const fileActions = (field: AdminExtensionSettingField): SettingFileActions => ({
        pending: uploadFile.isPending || clearFile.isPending,
        onUpload: (file) => uploadFile.mutate(uploadAdminExtensionSettingFileInput(id, field.input, file)),
        onClear: () => clearFile.mutate(clearAdminExtensionSettingFileInput(id, field.input)),
    });

    const submit = async () => {
        const settings = buildSettingsPayload(schema, getValue);

        try {
            await updateSettings.mutateAsync(updateAdminExtensionSettingsInput(id, settings));
            onClose();
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return (
        <Dialog
            open={open}
            title={`${extensionName(extension)} settings`}
            preventExternalClose={submitting}
            hideCloseIcon={submitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={submitting} />
            <SettingsDialogContent
                error={error}
                showLoading={showLoading}
                registered={registered}
                schema={schema}
                extensionEnabled={extension.enabled}
                getValue={getValue}
                onChange={handleChange}
                fileActions={fileActions}
            />
            <SettingsDialogActions
                submitting={submitting}
                canSave={registered && schema.length > 0}
                onCancel={onClose}
                onSubmit={submit}
            />
        </Dialog>
    );
}

function ExtensionCard({ extension }: { extension: AdminExtension }) {
    const enableExtension = useEnableAdminExtension();
    const disableExtension = useDisableAdminExtension();
    const removeExtension = useRemoveAdminExtension();
    const id = extensionId(extension);
    const name = extensionName(extension);
    const subtitle = [extension.version ? `v${extension.version}` : null, extension.author && `by ${extension.author}`]
        .filter(Boolean)
        .join(' · ');
    const state = extension.state as ExtensionState | undefined;
    const badge = stateBadge(state);
    const enablePending = enableExtension.isPending && enableExtension.variables?.path.extension === extension.id;
    const disablePending = disableExtension.isPending && disableExtension.variables?.path.extension === extension.id;
    const removePending = removeExtension.isPending && removeExtension.variables?.path.extension === extension.id;

    const submitEnable = () => {
        if (!extension.id) {
            return;
        }

        enableExtension.mutate(enableAdminExtensionInput(extension.id));
    };

    const submitDisable = () => {
        if (!extension.id) {
            return;
        }

        disableExtension.mutate(disableAdminExtensionInput(extension.id));
    };

    const submitRemove = (close: () => void) => {
        if (!extension.id) {
            return;
        }

        removeExtension.mutate(removeAdminExtensionInput(extension.id, name), {
            onSuccess: close,
        });
    };

    return (
        <article className='flex h-full flex-col rounded-sm border border-border bg-card p-4'>
            <div className='flex min-w-0 items-start gap-3'>
                <ExtensionMark extension={extension} />
                <div className='min-w-0 flex-1'>
                    <div className='flex flex-wrap items-center gap-2'>
                        <h2 className='truncate text-base font-semibold text-foreground' title={`${name} (${id})`}>
                            {name}
                        </h2>
                        {badge && (
                            <span className={cn('rounded-sm px-1.5 py-0.5 text-xs font-medium', badge.className)}>
                                {badge.label}
                            </span>
                        )}
                    </div>
                    <p className='mt-0.5 truncate text-xs text-muted-foreground'>
                        {subtitle && <>{subtitle} · </>}
                        <span className='font-mono'>{id}</span>
                    </p>
                </div>
            </div>

            <p
                className='mt-3 line-clamp-3 text-sm leading-relaxed text-card-foreground/80'
                title={extension.description || undefined}
            >
                {extension.description || 'No description provided.'}
            </p>

            {state === 'not_registered' && (
                <p className='mt-3 text-sm text-muted-foreground'>
                    Found in the extensions folder. Turn it on to finish setting it up.
                </p>
            )}

            {extension.error && (
                <div className='mt-3 rounded-sm border border-destructive/30 bg-destructive/10 p-3'>
                    <p className='break-words text-sm text-destructive'>{extension.error}</p>
                </div>
            )}

            <div className='mt-auto pt-4'>
                <div className='flex flex-wrap items-center gap-2 border-t border-border pt-4'>
                    <Switch
                        aria-label={`Enable ${name}`}
                        className='mr-auto'
                        checked={extension.enabled === true}
                        disabled={
                            enablePending ||
                            disablePending ||
                            !(extension.enabled ? canDisable(extension) : canEnable(extension))
                        }
                        onChange={(checked) => (checked ? submitEnable() : submitDisable())}
                    />
                    {extension.enabled && (
                        <Dialog.Trigger
                            trigger={({ onClick }) => (
                                <ActionButton label='Settings' icon={Settings2} onClick={onClick} />
                            )}
                        >
                            {({ open, onClose }) => (
                                <SettingsDialog extension={extension} open={open} onClose={onClose} />
                            )}
                        </Dialog.Trigger>
                    )}
                    <Dialog.ConfirmTrigger
                        title='Remove extension'
                        confirm='Remove'
                        preventExternalClose={removePending}
                        hideCloseIcon={removePending}
                        pending={removePending}
                        trigger={({ onClick }) => (
                            <ActionButton
                                label='Remove'
                                icon={Trash2}
                                color='red'
                                disabled={!canRemove(extension) || removePending}
                                isLoading={removePending}
                                onClick={onClick}
                            />
                        )}
                        onConfirmed={(_event, close) => submitRemove(close)}
                    >
                        <SpinnerOverlay visible={removePending} />
                        Removing <strong>{name}</strong> deletes the extension files, published assets, settings,
                        subuser permissions it added, and install record.
                    </Dialog.ConfirmTrigger>
                </div>
            </div>
        </article>
    );
}

function EmptyExtensionsState() {
    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <FileArchive />
                </EmptyMedia>
                <EmptyTitle>No extensions installed</EmptyTitle>
                <EmptyDescription>
                    Install an extension package to add features and integrations to this panel.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <InstallExtensionButton />
            </EmptyContent>
        </Empty>
    );
}

function ExtensionGrid({ extensions }: { extensions: AdminExtension[] }) {
    if (extensions.length === 0) {
        return <EmptyExtensionsState />;
    }

    return (
        <div className='grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3'>
            {extensions.map((extension) => (
                <ExtensionCard key={extension.id ?? extension.name} extension={extension} />
            ))}
        </div>
    );
}

export default function ExtensionsContainer() {
    const runtimeStates = useExtensionRegistry(getExtensionStates);
    const runtimeFailures = runtimeStates.filter((state) => state.status === 'failed');
    const { data, error, isFetching, refetch } = useAdminExtensions();
    const extensions = data?.data ?? [];
    const failedCount = extensions.filter((extension) => ['error', 'invalid'].includes(extension.state ?? '')).length;

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Extensions'
            heading='Extensions'
            description='Manage extensions that add features and integrations to your panel.'
        >
            {runtimeFailures.length > 0 && (
                <Alert type='danger' className='mb-4'>
                    {runtimeFailures.map((state) => (
                        <p key={state.id}>
                            {state.id}: {state.error}
                        </p>
                    ))}
                </Alert>
            )}
            {data?.meta?.enabled === false && (
                <Alert type='warning' className='mb-4'>
                    Extensions are turned off on this panel, so installed extensions won&apos;t load. Set{' '}
                    <code className='font-mono'>PTERODACTYL_EXTENSIONS_ENABLED=true</code> in your{' '}
                    <code className='font-mono'>.env</code> to turn them on.
                </Alert>
            )}
            {data && <ExtensionToolbar failedCount={failedCount} isFetching={isFetching} onRefresh={() => refetch()} />}
            {data ? <ExtensionGrid extensions={extensions} /> : <Spinner size='large' centered />}
        </AdminContentBlock>
    );
}
