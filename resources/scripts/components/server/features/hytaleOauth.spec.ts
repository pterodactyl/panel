import { describe, expect, it } from 'vitest';
import { extractHytaleOauthUrl } from './hytaleOauth';

const verifyUrl = 'https://oauth.accounts.hytale.com/oauth2/device/verify?user_code=AbCd-1234';

describe('extractHytaleOauthUrl', () => {
    it('extracts only the verification URL from a decorated console line', () => {
        expect(extractHytaleOauthUrl(verifyUrl)).toBe(verifyUrl);
        expect(extractHytaleOauthUrl(`[12:00:01 INFO] Please visit ${verifyUrl} to sign in.`)).toBe(verifyUrl);
        expect(extractHytaleOauthUrl(`\u001b[33m${verifyUrl}\u001b[0m`)).toBe(verifyUrl);
    });

    it('ignores lines without a verification URL on the Hytale accounts origin', () => {
        expect(extractHytaleOauthUrl('Server started')).toBeNull();
        expect(extractHytaleOauthUrl('/oauth2/device/verify?user_code=AbCd')).toBeNull();
        expect(
            extractHytaleOauthUrl('https://oauth.accounts.hytale.com.evil.test/oauth2/device/verify?user_code=AbCd')
        ).toBeNull();
        expect(
            extractHytaleOauthUrl(
                'https://evil.test/?next=https://oauth.accounts.hytale.com/oauth2/device/verify?user_code=x'
            )
        ).toBe('https://oauth.accounts.hytale.com/oauth2/device/verify?user_code=x');
        expect(extractHytaleOauthUrl('https://oauth.accounts.hytale.com/oauth2/device/verify?user_code=')).toBeNull();
    });
});
