import { useRef } from 'react';
import Avatar from '@/components/Avatar';
import { Button, TitledGreyBox } from '@/sdk';
import { useDeleteAdminUserAvatar, useUpdateAdminUserAvatar } from '@/api/admin/users/queries';
import type { AdminUser } from '@/api/admin/users/queries';

export default function UserAvatarCard({ user }: { user: AdminUser }) {
    const input = useRef<HTMLInputElement>(null);
    const update = useUpdateAdminUserAvatar();
    const remove = useDeleteAdminUserAvatar();
    const image = user.attributes.avatar;

    return (
        <TitledGreyBox title='Avatar'>
            <div className='flex items-center gap-4'>
                <Avatar name={user.attributes.username} image={image} size={72} />
                <input
                    ref={input}
                    type='file'
                    accept='.png,.jpg,.jpeg,.svg,.webp,.avif,.ico,image/png,image/jpeg,image/svg+xml,image/webp,image/avif,image/x-icon'
                    className='hidden'
                    onChange={(event) => {
                        const file = event.target.files?.[0];
                        if (file) {
                            void update.mutateAsync({ id: user.attributes.id, file });
                        }
                    }}
                />
                <div className='flex flex-wrap gap-2'>
                    <Button type='button' onClick={() => input.current?.click()} isLoading={update.isPending}>
                        Replace Avatar
                    </Button>
                    {image && (
                        <Button type='button' isSecondary onClick={() => void remove.mutateAsync(user.attributes.id)}>
                            Remove Avatar
                        </Button>
                    )}
                </div>
            </div>
        </TitledGreyBox>
    );
}
