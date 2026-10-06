import { describe, expect, it } from 'vitest';
import type { AdminDatabaseHost } from '@/api/admin/database-hosts/queries';
import {
    databaseHostBodyFromFormValues,
    databaseHostFormValues,
    databaseHostValidators,
    newDatabaseHostFormValues,
} from './databaseHostForm';

const host: AdminDatabaseHost = {
    object: 'database_host',
    attributes: {
        id: 4,
        name: 'Primary MySQL',
        host: 'mysql.internal',
        port: 3307,
        username: 'pterodactyl',
        max_databases: null,
        databases_count: 2,
        node_id: 8,
        created_at: '2026-09-01T00:00:00Z',
        updated_at: '2026-09-01T00:00:00Z',
    },
};

describe('database host form values', () => {
    it('starts a new host on the default MySQL port with no linked node', () => {
        expect(newDatabaseHostFormValues()).toEqual({
            name: '',
            host: '',
            port: 3306,
            username: '',
            password: '',
            nodeId: '',
            extensions: {},
        });
    });

    it('edits an existing host with a blank password', () => {
        expect(databaseHostFormValues(host)).toEqual({
            name: 'Primary MySQL',
            host: 'mysql.internal',
            port: 3307,
            username: 'pterodactyl',
            password: '',
            nodeId: '8',
            extensions: {},
        });
    });

    it('omits a blank password and unlinks an empty node from the request body', () => {
        expect(databaseHostBodyFromFormValues({ ...databaseHostFormValues(host), nodeId: '' })).toEqual({
            name: 'Primary MySQL',
            host: 'mysql.internal',
            port: 3307,
            username: 'pterodactyl',
            password: undefined,
            node_id: null,
            extensions: {},
        });
    });
});

describe('databaseHostValidators', () => {
    it('requires a port within range', () => {
        expect(databaseHostValidators.port.onChange({ value: null })).toBe('A port must be provided.');
        expect(databaseHostValidators.port.onChange({ value: 0 })).toBe('The port must be between 1 and 65535.');
        expect(databaseHostValidators.port.onChange({ value: 3306 })).toBeUndefined();
    });

    it('validates host names', () => {
        expect(databaseHostValidators.host.onChange({ value: '' })).toBe('A host must be provided.');
        expect(databaseHostValidators.host.onChange({ value: 'bad host' })).toBe(
            'The host must be a valid hostname or IP address.'
        );
        expect(databaseHostValidators.host.onChange({ value: '10.0.0.5' })).toBeUndefined();
    });

    it('requires a password only where the form asks for one', () => {
        expect(databaseHostValidators.requiredPassword.onChange({ value: '' })).toBe('A password must be provided.');
        expect(databaseHostValidators.requiredPassword.onChange({ value: 'secret' })).toBeUndefined();
    });
});
