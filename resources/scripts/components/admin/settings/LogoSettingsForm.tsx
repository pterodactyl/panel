import type { AdminSettings } from '@/api/admin/settings/queries';
import Button from '@/components/elements/Button';
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
    const hasPendingChange = pendingFile !== null || removePending;

    return (
        <div className='mt-6 border-t border-border pt-6'>
            <h3 className='text-base font-medium text-foreground'>Logo</h3>
            <p className='input-help'>
                Upload a logo to use as the Panel favicon, on the login page, and beside your company name.
            </p>
            <p className='input-help'>
                PNG, JPEG, WebP, AVIF, ICO, and SVG files up to 10 MB are supported.
            </p>
            {settings.logo && !removePending && (
                <div className='mt-6 flex items-center gap-4'>
                    <img
                        src={settings.logo}
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
            {pendingFile && <p className='mt-4 input-help'>Selected: {pendingFile.name}</p>}
            {hasPendingChange && (
                <p className='mt-4 text-sm text-warning'>Save Changes to apply the logo change.</p>
            )}
            <div className='mt-6'>
                <FileInput
                    aria-label='Upload logo'
                    accept={logoTypes}
                    disabled={readOnly || disabled}
                    onChange={(event) => {
                        onFileSelected(event.currentTarget.files?.[0]);
                        event.currentTarget.value = '';
                    }}
                />
            </div>
        </div>
    );
}
