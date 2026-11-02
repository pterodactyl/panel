import { useEffect, useState } from 'react';
import type { AdminSettings } from '@/api/admin/settings/queries';
import Button from '@/components/elements/Button';
import Label from '@/components/elements/Label';
import { FileInput } from '@/components/form/controls';

const logoTypes = 'image/png,image/jpeg,image/webp,image/avif,image/x-icon,image/svg+xml';

type Props = {
    settings: AdminSettings;
    pendingFile: File | null;
    removePending: boolean;
    disabled: boolean;
    onFileSelected: (file: File | undefined) => void;
    onRemove: () => void;
};

export default function LogoSettingsForm({
    settings,
    pendingFile,
    removePending,
    disabled,
    onFileSelected,
    onRemove,
}: Props) {
    const readOnly = settings.meta.load_environment_only;
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!pendingFile) {
            setPreviewUrl(null);

            return;
        }

        const url = URL.createObjectURL(pendingFile);
        setPreviewUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [pendingFile]);

    const logoPreview = removePending ? null : previewUrl ?? settings.logo;

    return (
        <div className='mt-6 border-t border-border pt-6'>
            <Label as='h3'>Logo</Label>
            <p className='input-help'>
                Upload a logo to use as the Panel favicon, on the login page, and beside your company name.
            </p>
            <p className='input-help'>
                PNG, JPEG, WebP, AVIF, ICO, and SVG files up to 10 MB are supported.
            </p>
            {logoPreview && (
                <div className='mt-6 flex items-center gap-4'>
                    <img
                        src={logoPreview}
                        alt='Current panel logo'
                        className='h-20 w-20 rounded-sm border border-border bg-background object-contain p-2'
                    />
                    <Button
                        type='button'
                        color='red'
                        isSecondary
                        disabled={readOnly || disabled}
                        onClick={onRemove}
                    >
                        Remove Logo
                    </Button>
                </div>
            )}
            <div className='mt-6'>
                <FileInput
                    aria-label='Upload logo'
                    accept={logoTypes}
                    disabled={readOnly || disabled}
                    onChange={(event) => {
                        onFileSelected(event.currentTarget.files?.[0]);
                    }}
                />
            </div>
        </div>
    );
}
