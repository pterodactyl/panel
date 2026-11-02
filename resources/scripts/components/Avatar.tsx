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
    /** An uploaded picture drawn in place of the pattern, such as a user's `avatar`. */
    image?: string | null;
}

function CoreAvatar({ variant = 'beam', image, className, ...props }: AvatarProps) {
    const ui = useSkin('Avatar', { variant });

    if (image) {
        // Shaped and sized like the pattern: round unless `square`, and as wide as its box without a `size`.
        return (
            <img
                src={image}
                alt={props.title ? props.name : ''}
                style={{
                    width: props.size ?? '100%',
                    height: props.size,
                    aspectRatio: '1',
                    objectFit: 'cover',
                    borderRadius: props.square ? undefined : '50%',
                }}
                {...ui('root', className)}
            />
        );
    }

    return <BoringAvatar colors={palette} variant={variant} {...props} {...ui('root', className)} />;
}

const _Avatar = replaceable('Avatar', CoreAvatar);

const UserAvatar = (props: Omit<AvatarProps, 'name'>) => {
    const user = useCurrentUser();

    return <_Avatar name={user.uuid} image={user.avatar} {...props} />;
};

UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: UserAvatar,
});

export default Avatar;
