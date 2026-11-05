import { useRef, useState } from 'react';
import { Button, TitledGreyBox } from '@/sdk';
import { useDeleteAdminLogo, useUploadAdminLogo } from '@/api/admin/settings/queries';
import type { AdminSettings } from '@/api/admin/settings/queries';
import { imageUploadAccept } from '@/lib/images';
import { useObjectUrl } from '@/plugins/useObjectUrl';

export default function LogoSettingsCard({ settings }: { settings: AdminSettings }) {
    const readOnly = settings.meta.load_environment_only;
    const input = useRef<HTMLInputElement>(null);
    const upload = useUploadAdminLogo();
    const remove = useDeleteAdminLogo();
    const logo = settings.general['app:logo'];
    const [selected, setSelected] = useState<File | null>(null);
    const picked = useObjectUrl(selected);
    const preview = selected ? picked : logo;
    const save = () => {
        if (selected) {
            upload.mutate({ body: { logo: selected } }, { onSuccess: () => setSelected(null) });
        }
    };

    return (
        <TitledGreyBox title='Logo' className='mt-6'>
            <div className='flex flex-col gap-4'>
                <div className='flex items-start gap-4'>
                    <div className='flex h-20 w-20 shrink-0 items-center justify-center rounded-sm border border-border bg-background p-2'>
                        {preview ? (
                            <img src={preview} alt='Panel logo' className='h-full w-full object-contain' />
                        ) : (
                            <img
                                src='/favicons/favicon.svg'
                                alt='Default logo'
                                className='h-full w-full object-contain'
                            />
                        )}
                    </div>
                    <p className='m-0 text-sm text-muted-foreground'>
                        Shown as the favicon, on the login page and in place of the company name in the navigation bar.
                        A theme with a logo of its own shows that instead. PNG, JPEG, SVG, WebP, AVIF, and ICO files are
                        supported.
                    </p>
                </div>
                <input
                    ref={input}
                    type='file'
                    aria-label='Logo file'
                    accept={imageUploadAccept}
                    className='hidden'
                    onChange={(event) => setSelected(event.target.files?.[0] ?? null)}
                />
                <div className='flex justify-end gap-2'>
                    <Button
                        type='button'
                        disabled={readOnly}
                        isLoading={upload.isPending}
                        onClick={() => (selected ? save() : input.current?.click())}
                    >
                        {selected ? 'Save Logo' : 'Upload Logo'}
                    </Button>
                    <Button.Danger
                        type='button'
                        disabled={readOnly || !logo || !!selected}
                        isLoading={remove.isPending}
                        onClick={() => remove.mutate({})}
                    >
                        Remove Logo
                    </Button.Danger>
                </div>
            </div>
        </TitledGreyBox>
    );
}
