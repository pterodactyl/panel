import type { AvatarProps } from 'boring-avatars';
import BoringAvatar from 'boring-avatars';
import { useCurrentUser } from '@/api/account/queries';

const palette = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

type Props = Omit<AvatarProps, 'colors'>;

const _Avatar = ({ variant = 'beam', ...props }: AvatarProps) => (
    <BoringAvatar colors={palette} variant={variant} {...props} />
);

const UserAvatar = ({ variant = 'beam', ...props }: Omit<Props, 'name'>) => {
    const uuid = useCurrentUser().uuid;

    return <BoringAvatar colors={palette} name={uuid} variant={variant} {...props} />;
};

_Avatar.displayName = 'Avatar';
UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: UserAvatar,
});

export default Avatar;
