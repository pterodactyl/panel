import { describe, expect, it } from 'vitest';
import { isTransferInProgress, isTransferSuccessful, normalizeTransferStatus } from './transfer';

describe('normalizeTransferStatus', () => {
    it('maps every status Wings publishes', () => {
        expect(normalizeTransferStatus('processing')).toBe('processing');
        expect(normalizeTransferStatus('cancelling')).toBe('cancelling');
        expect(normalizeTransferStatus('failure')).toBe('failed');
        expect(normalizeTransferStatus('completed')).toBe('completed');
        expect(normalizeTransferStatus('success')).toBe('success');
    });

    it('folds the internal failure states into failed', () => {
        expect(normalizeTransferStatus('failed')).toBe('failed');
        expect(normalizeTransferStatus('cancelled')).toBe('failed');
        expect(normalizeTransferStatus(' FAILURE ')).toBe('failed');
    });

    it('rejects unknown payloads', () => {
        expect(normalizeTransferStatus('starting')).toBeNull();
        expect(normalizeTransferStatus('constructor')).toBeNull();
        expect(normalizeTransferStatus('')).toBeNull();
    });
});

describe('transfer status phases', () => {
    it('separates in-progress, failed and successful statuses', () => {
        expect(isTransferInProgress('pending')).toBe(true);
        expect(isTransferInProgress('processing')).toBe(true);
        expect(isTransferInProgress('cancelling')).toBe(true);
        expect(isTransferInProgress('failed')).toBe(false);
        expect(isTransferSuccessful('failed')).toBe(false);
        expect(isTransferSuccessful('completed')).toBe(true);
        expect(isTransferSuccessful('success')).toBe(true);
        expect(isTransferInProgress('completed')).toBe(false);
    });
});
