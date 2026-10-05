import { describe, expect, it } from 'vitest';
import type { ServerBackup } from '@/api/server/backups/queries';
import { completeBackup, parseBackupCompletedPayload } from './backupCompletion';

const pendingBackup: ServerBackup = {
    object: 'backup',
    attributes: {
        uuid: '0b8d2f0e-7d1c-4f8e-9d55-3f4c7d0a9b11',
        is_successful: false,
        is_locked: true,
        name: 'Nightly',
        ignored_files: [],
        checksum: null,
        bytes: 0,
        created_at: '2026-10-05T10:00:00Z',
        completed_at: null,
    },
};

const completedAt = '2026-10-05T10:05:00Z';

describe('parseBackupCompletedPayload', () => {
    it('parses the payloads Wings publishes', () => {
        expect(
            parseBackupCompletedPayload(
                JSON.stringify({
                    uuid: pendingBackup.attributes.uuid,
                    is_successful: false,
                    checksum: '',
                    checksum_type: 'sha1',
                    file_size: 0,
                })
            )
        ).toEqual({
            uuid: pendingBackup.attributes.uuid,
            is_successful: false,
            checksum: '',
            checksum_type: 'sha1',
            file_size: 0,
        });
    });

    it('rejects payloads without a uuid or a boolean outcome', () => {
        expect(parseBackupCompletedPayload(JSON.stringify({ is_successful: true }))).toBeNull();
        expect(parseBackupCompletedPayload(JSON.stringify({ uuid: 'a', is_successful: 'false' }))).toBeNull();
        expect(
            parseBackupCompletedPayload(JSON.stringify({ uuid: 'a', is_successful: true, file_size: '10' }))
        ).toBeNull();
        expect(parseBackupCompletedPayload('not json')).toBeNull();
    });
});

describe('completeBackup', () => {
    it('marks a failed backup as failed, unlocked and without checksum or size', () => {
        const failed = completeBackup(
            pendingBackup,
            {
                uuid: pendingBackup.attributes.uuid,
                is_successful: false,
                checksum: '',
                checksum_type: 'sha1',
                file_size: 0,
            },
            completedAt
        );

        expect(failed.attributes).toMatchObject({
            is_successful: false,
            is_locked: false,
            checksum: null,
            bytes: 0,
            completed_at: completedAt,
        });
    });

    it('records the checksum and size of a successful backup', () => {
        const succeeded = completeBackup(
            pendingBackup,
            {
                uuid: pendingBackup.attributes.uuid,
                is_successful: true,
                checksum: 'abc123',
                checksum_type: 'sha1',
                file_size: 2048,
            },
            completedAt
        );

        expect(succeeded.attributes).toMatchObject({
            is_successful: true,
            is_locked: true,
            checksum: 'sha1:abc123',
            bytes: 2048,
            completed_at: completedAt,
        });
    });

    it('keeps the cached checksum and size when a successful event omits them', () => {
        const cached = {
            ...pendingBackup,
            attributes: { ...pendingBackup.attributes, checksum: 'sha1:old', bytes: 10 },
        };

        expect(
            completeBackup(cached, { uuid: cached.attributes.uuid, is_successful: true }, completedAt).attributes
        ).toMatchObject({ checksum: 'sha1:old', bytes: 10, is_successful: true });
    });
});
