import React from 'react';
import { useStoreState } from '@/state/hooks';
import { gravatarUrl } from '@/lib/gravatar';

interface Props extends React.ImgHTMLAttributes<HTMLImageElement> {
    /** Optional email to use for gravatar hashing. Falls back to `name` or `'system'`. */
    email?: string | null;
    /** Optional seed to use when email is not available. */
    name?: string | null;
    size?: number;
}

const _Avatar = ({ email, name, size = 48, className, style, alt, ...props }: Props) => {
    const url = gravatarUrl(email ?? name ?? '');

    return <img src={url.replace(/s=48/, `s=${size}`)} width={size} height={size} className={className} style={style} alt={alt ?? 'Avatar'} {...props} />;
};

const _UserAvatar = ({ size = 48, className, style, alt, ...props }: Omit<Props, 'name'>) => {
    const email = useStoreState((state) => state.user.data?.email);

    const url = gravatarUrl(email ?? '');

    return <img src={url.replace(/s=48/, `s=${size}`)} width={size} height={size} className={className} style={style} alt={alt ?? 'User avatar'} {...props} />;
};

_Avatar.displayName = 'Avatar';
_UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: _UserAvatar,
});

export default Avatar;
