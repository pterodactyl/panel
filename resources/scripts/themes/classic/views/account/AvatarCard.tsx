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
            <div className='flex items-center gap-4'>
                <Avatar name='profile' image={preview} size={72} />
                <div className='flex flex-wrap gap-2'>
                    <input
                        ref={input}
                        type='file'
                        accept='.png,.jpg,.jpeg,.svg,.webp,.avif,.ico,image/png,image/jpeg,image/svg+xml,image/webp,image/avif,image/x-icon'
                        className='hidden'
                        onChange={(event) => model.select(event.target.files?.[0] ?? null)}
                    />
                    <Button type='button' onClick={() => input.current?.click()}>
                        Choose Avatar
                    </Button>
                    {model.avatar && !model.selected && (
                        <Button type='button' isSecondary onClick={model.remove}>
                            Remove Avatar
                        </Button>
                    )}
                    {model.selected && (
                        <Button type='button' onClick={model.submit} disabled={!model.canSubmit}>
                            Update Avatar
                        </Button>
                    )}
                </div>
            </div>
        </ContentBox>
    );
}
