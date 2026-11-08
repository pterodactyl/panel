import { describe, expect, it } from 'vitest';
import { generateSecurePassword } from '@/lib/passwords';

describe('generateSecurePassword', () => {
    it('defaults to 17 characters with one from every set', () => {
        for (let attempt = 0; attempt < 50; attempt++) {
            const password = generateSecurePassword();

            expect(password).toHaveLength(17);
            expect(password).toMatch(/[A-Z]/);
            expect(password).toMatch(/[a-z]/);
            expect(password).toMatch(/[0-9]/);
            expect(password).toMatch(/[!@#$%^&*()_+=\-[\]{}]/);
        }
    });

    it('honours a longer length and never goes below one character per set', () => {
        expect(generateSecurePassword(32)).toHaveLength(32);
        expect(generateSecurePassword(2)).toHaveLength(4);
    });

    it('does not repeat itself', () => {
        expect(new Set(Array.from({ length: 20 }, () => generateSecurePassword())).size).toBe(20);
    });
});
