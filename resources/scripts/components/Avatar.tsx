import BoringAvatar, { AvatarProps } from 'boring-avatars';
import { useStoreState } from '@/state/hooks';
import crypto from 'crypto';

const palette = ['#FFAD08', '#EDD75A', '#73B06F', '#0C8F8F', '#587291'];

type Props = Omit<AvatarProps, 'colors'>;

const _Avatar = ({ variant = 'beam', ...props }: AvatarProps) => (
    <BoringAvatar colors={palette} variant={variant} {...props} />
);

const _UserAvatar = ({ variant = 'beam', ...props }: Omit<Props, 'name'>) => {
    const avatar = useStoreState((state) => `https://www.gravatar.com/avatar/${crypto.createHash('md5').update(state.user.data!.email).digest('hex')}?s=512`);

    return <img src={avatar} className="user-image" alt="User Image" />;
};

_Avatar.displayName = 'Avatar';
_UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: _UserAvatar,
});

export default Avatar;
