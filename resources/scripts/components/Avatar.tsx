import BoringAvatar from 'boring-avatars';
import { useCurrentUser } from '@/api/account/queries';
import { replaceable, useSkin } from '@/themes/skins';

const palette = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

export type AvatarVariant = 'beam' | 'marble' | 'pixel' | 'sunset' | 'ring' | 'bauhaus';

export interface AvatarProps {
    /** Seeds the generated pattern; the panel uses a user's UUID. */
    name: string;
    size?: number | string;
    variant?: AvatarVariant;
    square?: boolean;
    title?: boolean;
    className?: string;
}

function CoreAvatar({ variant = 'beam', className, ...props }: AvatarProps) {
    const ui = useSkin('Avatar', { variant });

    return <BoringAvatar colors={palette} variant={variant} {...props} {...ui('root', className)} />;
}

const _Avatar = replaceable('Avatar', CoreAvatar);

const UserAvatar = (props: Omit<AvatarProps, 'name'>) => <_Avatar name={useCurrentUser().uuid} {...props} />;

UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: UserAvatar,
});

export default Avatar;
