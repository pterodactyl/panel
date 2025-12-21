import React from 'react';
import { useStoreState } from '@/state/hooks';
import { gravatarUrl } from '@/lib/gravatar';

interface Props extends React.ImgHTMLAttributes<HTMLImageElement> {
    /** Email to use for Gravatar hashing. */
    email?: string | null;
    /** Fallback seed when email isn't available. */
    name?: string | null;
    size?: number;
}

const _Avatar = ({ email, name, size = 48, alt, className, style, ...props }: Props) => {
    const src = gravatarUrl(email ?? name ?? '', size);
    return (
        <img
            src={src}
            width={size}
            height={size}
            className={className}
            style={style}
            alt={alt ?? 'Avatar'}
            {...props}
        />
    );
};

const _UserAvatar = ({ size = 48, alt, className, style, ...props }: Omit<Props, 'name'>) => {
    const email = useStoreState((state) => state.user.data?.email);
    const src = gravatarUrl(email ?? '', size);

    return (
        <img
            src={src}
            width={size}
            height={size}
            className={className}
            style={style}
            alt={alt ?? 'User avatar'}
            {...props}
        />
    );
};

_Avatar.displayName = 'Avatar';
_UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: _UserAvatar,
});

export default Avatar;
