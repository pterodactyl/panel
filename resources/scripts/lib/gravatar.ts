import md5 from 'md5';

/**
 * Return a Gravatar URL using the same hashing strategy the server uses:
 * md5(strtolower(trim(email))).
 */
export function gravatarUrl(value: string | undefined, size = 48) {
    const seed = (value || '').trim().toLowerCase();
    const hash = md5(seed);
    return `https://www.gravatar.com/avatar/${hash}?s=${size}&d=identicon`;
}

export default gravatarUrl;
