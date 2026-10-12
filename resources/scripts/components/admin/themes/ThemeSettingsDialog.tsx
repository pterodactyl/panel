import { useEffect, useRef, useState } from 'react';
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
import { createLivePreview, type LivePreview } from './livePreview';

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

/** The settings whose live changes the page shows: those of the theme it renders. */
const rendersTheme = (theme: AdminTheme) => document.documentElement.dataset.theme === theme.id;

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
    const preview = useRef<LivePreview | null>(null);
    const live = rendersTheme(theme);
    const settings = (data?.data ?? []).map((item) => item.attributes);

    // Whatever the page still shows unsaved when the dialog goes away is put back.
    useEffect(() => () => preview.current?.restore(), []);

    const change = (setting: AdminThemeSetting) => (input: AdminExtensionSettingValue) => {
        // Theme settings are single values; only list fields hand over arrays.
        const value = Array.isArray(input) ? null : input;

        setValues((current) => ({ ...current, [setting.key]: value }));

        if (live) {
            preview.current ??= createLivePreview();
            preview.current.apply(setting, value);
        }
    };

    const cancel = () => {
        preview.current?.restore();
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
            preview.current?.keep();
            setValues({});
            onClose();
        } catch {
            // The mutation reports the error.
        }
    };

    const fileActions = (setting: AdminThemeSetting) => ({
        pending: upload.isPending || clear.isPending,
        onUpload: (file: File) =>
            upload.mutate(uploadAdminThemeImageInput(theme.id, setting.key, file), {
                onSuccess: (fresh) => {
                    const stored = fresh.data.find((item) => item.attributes.key === setting.key)?.attributes;

                    if (live && stored) {
                        preview.current ??= createLivePreview();
                        preview.current.apply(stored, stored.value);
                        preview.current.keep();
                    }
                },
            }),
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
                    {!live && (
                        <Alert type='info'>Changes show on this page once you activate or preview this theme.</Alert>
                    )}
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
