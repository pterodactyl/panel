const HYTALE_OAUTH_ORIGIN = 'https://oauth.accounts.hytale.com';
const HYTALE_DEVICE_VERIFY_PATH = '/oauth2/device/verify';

export const hytaleOauthUrlPattern = /https:\/\/oauth\.accounts\.hytale\.com\/oauth2\/device\/verify\?user_code=[\w-]+/;

export const extractHytaleOauthUrl = (line: string): string | null => {
    const match = hytaleOauthUrlPattern.exec(line);

    if (!match) {
        return null;
    }

    const url = new URL(match[0]);

    if (
        url.origin !== HYTALE_OAUTH_ORIGIN ||
        url.pathname !== HYTALE_DEVICE_VERIFY_PATH ||
        !url.searchParams.get('user_code')
    ) {
        return null;
    }

    return url.href;
};
