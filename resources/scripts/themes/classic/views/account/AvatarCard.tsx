import { useRef } from 'react';
import { Avatar, Button, ContentBox, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function AvatarCard({ model, className }: ViewProps<'account.avatar'>) {
    const input = useRef<HTMLInputElement>(null);
    const { t } = useTranslation();

    return (
        <ContentBox className={className} title={t('Update Avatar')}>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <div className='flex flex-col gap-4'>
                <div className='flex items-start gap-4'>
                    <div className='h-[72px] w-[72px] shrink-0'>
                        <Avatar name={model.uuid} image={model.preview} />
                    </div>
                    <p className='m-0 text-sm'>
                        {t(
                            'You may select a custom avatar for your personal account. PNG, JPEG, SVG, WebP, AVIF, and ICO files are supported.'
                        )}
                    </p>
                </div>
                <div className='flex flex-wrap gap-2'>
                    <input
                        ref={input}
                        type='file'
                        accept={model.accept}
                        className='hidden'
                        aria-label={t('Avatar file')}
                        onChange={(event) => model.select(event.target.files?.[0] ?? null)}
                    />
                    <Button
                        type='button'
                        onClick={() => (model.selected ? model.submit() : input.current?.click())}
                        disabled={model.selected ? !model.canSubmit : false}
                    >
                        {model.selected ? t('Save Changes') : t('Update Avatar')}
                    </Button>
                    <Button.Danger type='button' disabled={!model.avatar || !!model.selected} onClick={model.remove}>
                        {t('Remove Avatar')}
                    </Button.Danger>
                </div>
            </div>
        </ContentBox>
    );
}
