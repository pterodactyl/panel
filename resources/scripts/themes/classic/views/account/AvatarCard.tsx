import { useEffect, useRef, useState } from 'react';
import Avatar from '@/components/Avatar';
import { Button, ContentBox, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function AvatarCard({ model, className }: ViewProps<'account.avatar'>) {
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(model.avatar);

    useEffect(() => {
        if (!model.selected) {
            setPreview(model.avatar);

            return;
        }

        const url = URL.createObjectURL(model.selected);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [model.avatar, model.selected]);

    return (
        <ContentBox className={className} title='Update Avatar'>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <div className='flex flex-col gap-4'>
                <div className='flex items-start gap-4'>
                    <Avatar name='profile' image={preview} size={72} />
                    <p className='input-help m-0'>
                        You may select a custom avatar for your personal account. Avatars are not required and the
                        default one will always be used if none is set.
                    </p>
                </div>
                <div className='flex flex-wrap gap-2'>
                    <input
                        ref={input}
                        type='file'
                        accept='.png,.jpg,.jpeg,.svg,.webp,.avif,.ico,image/png,image/jpeg,image/svg+xml,image/webp,image/avif,image/x-icon'
                        className='hidden'
                        onChange={(event) => model.select(event.target.files?.[0] ?? null)}
                    />
                    <Button
                        type='button'
                        onClick={() => (model.selected ? model.submit() : input.current?.click())}
                        disabled={model.selected ? !model.canSubmit : false}
                    >
                        {model.selected ? 'Save Changes' : 'Update Avatar'}
                    </Button>
                    <Button.Danger type='button' disabled={!model.avatar || !!model.selected} onClick={model.remove}>
                        Remove Avatar
                    </Button.Danger>
                </div>
            </div>
        </ContentBox>
    );
}
