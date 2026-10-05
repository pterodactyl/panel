import { describe, expect, it } from 'vitest';
import { newNodeFormValues, nodeNumberValidators, nodeValuesFromForm } from './nodeForm';

describe('node form', () => {
    it('submits the entered numbers, including zero overallocation', () => {
        expect(nodeValuesFromForm({ ...newNodeFormValues(), memoryOverallocate: 0, daemonListen: 8443 })).toEqual(
            expect.objectContaining({ memory: 1024, memoryOverallocate: 0, daemonListen: 8443, daemonSftp: 2022 })
        );
    });

    it('rejects emptied number inputs', () => {
        expect(nodeNumberValidators.memory.onChange({ value: null })).toBe('A memory limit must be provided.');
        expect(nodeNumberValidators.memoryOverallocate.onChange({ value: null })).toBe(
            'A memory overallocation must be provided.'
        );
        expect(nodeNumberValidators.daemonSftp.onChange({ value: null })).toBe('A daemon SFTP port must be provided.');
    });

    it('keeps the existing range checks for entered numbers', () => {
        expect(nodeNumberValidators.disk.onChange({ value: 0 })).toBe('A disk limit must be provided.');
        expect(nodeNumberValidators.disk.onChange({ value: 1 })).toBeUndefined();
        expect(nodeNumberValidators.memoryOverallocate.onChange({ value: -1 })).toBeUndefined();
    });
});
