import { useEffect, useRef, useState } from 'react';
import Avatar from '@/components/Avatar';
import { Button, TitledGreyBox } from '@/sdk';
import { useDeleteAdminUserAvatar, useUpdateAdminUserAvatar } from '@/api/admin/users/queries';
import type { AdminUser } from '@/api/admin/users/queries';

export default function UserAvatarCard({ user }: { user: AdminUser }) {
    const input = useRef<HTMLInputElement>(null);
    const update = useUpdateAdminUserAvatar();
    const remove = useDeleteAdminUserAvatar();
    const image = user.attributes.avatar;
    const [selected, setSelected] = useState<File | null>(null);
    const [preview, setPreview] = useState(image);

    useEffect(() => {
        if (!selected) {
            setPreview(image);

            return;
        }

        const url = URL.createObjectURL(selected);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [image, selected]);

    const save = async () => {
        if (!selected) {
            return;
        }

        await update.mutateAsync({ id: user.attributes.id, file: selected });
        setSelected(null);
    };

    return (
        <TitledGreyBox title='Avatar'>
            <div className='flex flex-col gap-4'>
                <div className='flex items-start gap-4'>
                    <Avatar name={user.attributes.username} image={preview} size={72} />
                    <p className='m-0 text-sm text-muted-foreground'>
                        The avatar which will appear under the user&apos;s personal account. You may set a custom one or
                        use the default one provided by the Panel automatically.
                    </p>
                </div>
                <input
                    ref={input}
                    type='file'
                    accept='.png,.jpg,.jpeg,.svg,.webp,.avif,.ico,image/png,image/jpeg,image/svg+xml,image/webp,image/avif,image/x-icon'
                    className='hidden'
                    onChange={(event) => {
                        const file = event.target.files?.[0];
                        setSelected(file ?? null);
                    }}
                />
                <div className='flex justify-end gap-2'>
                    <Button
                        type='button'
                        isLoading={update.isPending}
                        onClick={() => (selected ? void save() : input.current?.click())}
                    >
                        {selected ? 'Save Changes' : 'Update Avatar'}
                    </Button>
                    <Button.Danger
                        type='button'
                        disabled={!image || !!selected}
                        isLoading={remove.isPending}
                        onClick={() => void remove.mutateAsync(user.attributes.id).then(() => setSelected(null))}
                    >
                        Remove Avatar
                    </Button.Danger>
                </div>
            </div>
        </TitledGreyBox>
    );
}
