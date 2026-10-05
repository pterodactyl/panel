import { describe, expect, it } from 'vitest';
import { requiredNumber, submittedNumber } from './numberInput';

describe('requiredNumber', () => {
    const validate = requiredNumber('A memory limit must be provided.', (value) =>
        value >= 0 ? undefined : 'Invalid memory limit.'
    );

    it('rejects an empty input even when the range check would accept null', () => {
        expect(validate({ value: null })).toBe('A memory limit must be provided.');
    });

    it('checks an entered number', () => {
        expect(validate({ value: 0 })).toBeUndefined();
        expect(validate({ value: 512 })).toBeUndefined();
        expect(validate({ value: -1 })).toBe('Invalid memory limit.');
    });

    it('accepts any entered number without a check', () => {
        expect(requiredNumber('A port must be provided.')({ value: 0 })).toBeUndefined();
    });
});

describe('submittedNumber', () => {
    it('returns an entered number, including zero', () => {
        expect(submittedNumber(0)).toBe(0);
        expect(submittedNumber(25565)).toBe(25565);
    });

    it('refuses an empty input', () => {
        expect(() => submittedNumber(null)).toThrow();
    });
});
