import type { AdminSettings } from '@/api/admin/settings/queries';
import { useClearAdminLogo, useUploadAdminLogo } from '@/api/admin/settings/queries';
import Button from '@/components/elements/Button';
import { FileInput } from '@/components/form/controls';

const logoTypes = 'image/png,image/jpeg,image/gif,image/webp,image/avif,image/x-icon,image/svg+xml';

export default function LogoSettingsForm({ settings }: { settings: AdminSettings }) {
    const uploadLogo = useUploadAdminLogo();
    const clearLogo = useClearAdminLogo();
    const readOnly = settings.meta.load_environment_only;

    const onFileSelected = (file: File | undefined) => {
        if (file) {
            uploadLogo.mutate(file);
        }
    };

    return (
        <div className='mt-6 border-t border-border pt-6'>
            <h3 className='text-base font-medium text-foreground'>Logo</h3>
            <p className='text-sm text-muted-foreground'>
                Upload a logo to use as the Panel favicon, on the login page, and beside your company name.
            </p>
            <p className='mt-2 text-xs text-muted-foreground'>
                PNG, JPEG, GIF, WebP, AVIF, ICO, and SVG files up to 10 MB are supported.
            </p>
            {settings.logo && (
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
                        disabled={readOnly || uploadLogo.isPending || clearLogo.isPending}
                        onClick={() => clearLogo.mutate()}
                    >
                        Remove Logo
                    </Button>
                </div>
            )}
            <div className='mt-6'>
                <FileInput
                    aria-label='Upload logo'
                    accept={logoTypes}
                    disabled={readOnly || uploadLogo.isPending || clearLogo.isPending}
                    onChange={(event) => {
                        onFileSelected(event.currentTarget.files?.[0]);
                        event.currentTarget.value = '';
                    }}
                />
            </div>
        </div>
    );
}
