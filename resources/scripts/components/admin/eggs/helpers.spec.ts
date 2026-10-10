import { describe, expect, it } from 'vitest';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import { eggToFormValues, toApiValues } from './helpers';

const egg = (config: Pick<AdminEgg['attributes']['config'], 'files' | 'startup' | 'logs'>): AdminEgg => ({
    object: 'egg',
    attributes: {
        id: 2,
        uuid: '00000000-0000-0000-0000-000000000002',
        author: 'support@pterodactyl.io',
        name: 'Child',
        description: null,
        features: null,
        docker_image: 'ghcr.io/pterodactyl/yolks:java_21',
        docker_images: { 'ghcr.io/pterodactyl/yolks:java_21': 'ghcr.io/pterodactyl/yolks:java_21' },
        force_outgoing_ip: false,
        config: { ...config, stop: 'stop', file_denylist: null, extends: 1 },
        startup: 'java -jar server.jar',
        script: { privileged: true, install: null, entry: 'bash', container: 'alpine', extends: null },
        created_at: '2026-10-09T00:00:00+00:00',
        updated_at: '2026-10-09T00:00:00+00:00',
    },
});

const roundTrip = (value: AdminEgg) => {
    const values = eggToFormValues(value);

    return toApiValues(values, values);
};

describe('egg configuration round trip', () => {
    it('keeps an explicitly empty configuration', () => {
        expect(roundTrip(egg({ files: {}, startup: {}, logs: {} }))).toMatchObject({
            config_files: '{}',
            config_startup: '{}',
            config_logs: '{}',
        });
    });

    it('keeps an inherited configuration inherited', () => {
        expect(roundTrip(egg({ files: null, startup: null, logs: null }))).toMatchObject({
            config_files: null,
            config_startup: null,
            config_logs: null,
        });
    });
});
