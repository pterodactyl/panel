import { useState } from 'react';
import type { AdminExtensionSettingField, AdminExtensionSettingValue } from '@/api/admin/extensions/queries';
import {
    clearAdminThemeImageInput,
    updateAdminThemeSettingsInput,
    uploadAdminThemeImageInput,
    type AdminTheme,
    type AdminThemeSetting,
    type AdminThemeSettingValue,
    useAdminThemeSettings,
    useClearAdminThemeImage,
    useUpdateAdminThemeSettings,
    useUploadAdminThemeImage,
} from '@/api/admin/themes/queries';
import { httpErrorToHuman } from '@/api/http';
import SettingField from '@/components/admin/extensions/SettingField';
import Button from '@/components/elements/Button';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Alert } from '@/components/elements/alert';
import { Dialog } from '@/components/elements/dialog';

/** The extension settings form renders theme settings too; an image is its file field. */
export const settingField = (setting: AdminThemeSetting): AdminExtensionSettingField => ({
    input: setting.key,
    label: setting.label,
    help:
        [setting.help, setting.type === 'number' && setting.unit ? `In ${setting.unit}.` : null]
            .filter(Boolean)
            .join(' ') || null,
    tab: null,
    field: setting.type === 'image' ? 'file' : setting.type,
    options: setting.options,
    value: setting.value,
    constraints: {
        max_length: setting.max_length,
        max_items: null,
        max_kilobytes: setting.max_kilobytes,
        accept: setting.accept,
    },
    visibility: 'admin',
});

/** An untouched setting sends its stored value; a cleared number sends null, which restores its default. */
const submitted = (setting: AdminThemeSetting, value: AdminThemeSettingValue | undefined): AdminThemeSettingValue => {
    if (value === undefined) {
        return setting.value;
    }

    return setting.type === 'number' && value === '' ? null : value;
};

export default function ThemeSettingsDialog({
    theme,
    open,
    onClose,
}: {
    theme: AdminTheme;
    open: boolean;
    onClose: () => void;
}) {
    const { data, error, isFetching } = useAdminThemeSettings(theme.id, { enabled: open });
    const update = useUpdateAdminThemeSettings();
    const upload = useUploadAdminThemeImage();
    const clear = useClearAdminThemeImage();
    const [values, setValues] = useState<Record<string, AdminThemeSettingValue | undefined>>({});
    const settings = (data?.data ?? []).map((item) => item.attributes);

    // Theme settings are single values; only list fields hand over arrays.
    const change = (setting: AdminThemeSetting) => (input: AdminExtensionSettingValue) =>
        setValues((current) => ({ ...current, [setting.key]: Array.isArray(input) ? null : input }));

    const cancel = () => {
        setValues({});
        onClose();
    };

    const save = async () => {
        const body: Record<string, AdminThemeSettingValue> = {};

        for (const setting of settings) {
            if (setting.type !== 'image') {
                body[setting.key] = submitted(setting, values[setting.key]);
            }
        }

        try {
            await update.mutateAsync(updateAdminThemeSettingsInput(theme.id, body));
            setValues({});
            onClose();
        } catch {
            // The mutation reports the error.
        }
    };

    const fileActions = (setting: AdminThemeSetting) => ({
        pending: upload.isPending || clear.isPending,
        onUpload: (file: File) => upload.mutate(uploadAdminThemeImageInput(theme.id, setting.key, file)),
        onClear: () => clear.mutate(clearAdminThemeImageInput(theme.id, setting.key)),
    });

    return (
        <Dialog
            open={open}
            title={`${theme.name} settings`}
            preventExternalClose={update.isPending}
            hideCloseIcon={update.isPending}
            onClose={cancel}
        >
            <SpinnerOverlay visible={update.isPending} />
            {error && <Alert type='danger'>{httpErrorToHuman(error)}</Alert>}
            {!error && !data && isFetching && <Spinner size='large' centered />}
            {!error && data && (
                <div className='space-y-5'>
                    {settings.map((setting) => (
                        <SettingField
                            key={setting.key}
                            field={settingField(setting)}
                            value={setting.key in values ? values[setting.key] : setting.value}
                            onChange={change(setting)}
                            file={setting.type === 'image' ? fileActions(setting) : undefined}
                        />
                    ))}
                </div>
            )}
            <div className='mt-6 flex flex-wrap justify-end'>
                <Button
                    type='button'
                    isSecondary
                    className='w-full sm:mr-2 sm:w-auto'
                    disabled={update.isPending}
                    onClick={cancel}
                >
                    Cancel
                </Button>
                <Button
                    type='button'
                    className='mt-4 w-full sm:mt-0 sm:w-auto'
                    disabled={update.isPending || settings.length === 0}
                    isLoading={update.isPending}
                    onClick={() => void save()}
                >
                    Save Settings
                </Button>
            </div>
        </Dialog>
    );
}
