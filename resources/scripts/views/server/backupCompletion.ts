import type { ServerBackup } from '@/api/server/backups/queries';
import { isFiniteNumber, isObject, isString } from '@/lib/objects';

/** Payload of the `backup completed` socket event published by Wings. */
export interface BackupCompletedPayload {
    uuid: string;
    is_successful: boolean;
    checksum?: string;
    checksum_type?: string;
    file_size?: number;
}

const isBackupCompletedPayload = <T>(value: T): value is T & BackupCompletedPayload =>
    isObject(value) &&
    'uuid' in value &&
    isString(value.uuid) &&
    'is_successful' in value &&
    (value.is_successful === true || value.is_successful === false) &&
    (!('checksum' in value) || isString(value.checksum)) &&
    (!('checksum_type' in value) || isString(value.checksum_type)) &&
    (!('file_size' in value) || isFiniteNumber(value.file_size));

export const parseBackupCompletedPayload = (data: string): BackupCompletedPayload | null => {
    try {
        const parsed: unknown = JSON.parse(data);

        return isBackupCompletedPayload(parsed) ? parsed : null;
    } catch {
        return null;
    }
};

export const completeBackup = (
    backup: ServerBackup,
    payload: BackupCompletedPayload,
    completedAt: string
): ServerBackup => {
    const { attributes } = backup;

    if (!payload.is_successful) {
        return {
            ...backup,
            attributes: {
                ...attributes,
                is_successful: false,
                is_locked: false,
                checksum: null,
                bytes: 0,
                completed_at: completedAt,
            },
        };
    }

    return {
        ...backup,
        attributes: {
            ...attributes,
            is_successful: true,
            checksum:
                payload.checksum && payload.checksum_type
                    ? `${payload.checksum_type}:${payload.checksum}`
                    : attributes.checksum,
            bytes: payload.file_size ?? attributes.bytes,
            completed_at: completedAt,
        },
    };
};
