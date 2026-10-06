import { isString } from '@/lib/objects';

export type RedirectSearch = {
    redirect?: string;
};

const redirectOrigin = 'http://redirect.invalid';

export const parseRedirectSearch = (search: { redirect?: unknown }): RedirectSearch => {
    const redirect = isString(search.redirect) ? search.redirect.trim() : '';
    if (!redirect.startsWith('/')) return {};

    let url: URL;
    try {
        url = new URL(redirect, redirectOrigin);
    } catch {
        return {};
    }

    if (url.origin !== redirectOrigin || url.pathname === '/auth' || url.pathname.startsWith('/auth/')) return {};

    const path = `${url.pathname}${url.search}${url.hash}`;

    return path === '/' ? {} : { redirect: path };
};
