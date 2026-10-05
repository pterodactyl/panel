import { describe, expect, it } from 'vitest';
import type { useAdminNodeConfiguration } from '@/api/admin/nodes/queries';
import { toYaml } from './nodeConfigurationYaml';

type NodeConfiguration = NonNullable<ReturnType<typeof useAdminNodeConfiguration>['data']>;

const configuration: NodeConfiguration = {
    debug: false,
    uuid: '0e7f2a52-3b8d-4c1f-9f61-5a2c0d7c9b11',
    token_id: 'Yq3vR8kP2mN6tX1w',
    token: 'k9#Lm2: pQ7"vX4\\zR8 # tail',
    api: {
        host: '0.0.0.0',
        port: 8080,
        ssl: {
            enabled: true,
            cert: '/etc/letsencrypt/live/node.example.com/fullchain.pem',
            key: '/etc/letsencrypt/live/node.example.com/privkey.pem',
        },
        upload_limit: 100,
    },
    system: {
        data: '/var/lib/pterodactyl/volumes',
        sftp: {
            bind_port: 2022,
        },
    },
    allowed_mounts: ['/mnt/shared', '/srv/minecraft maps'],
    remote: 'https://panel.example.com',
};

describe('toYaml', () => {
    it('serializes a node configuration into a config.yml document', () => {
        expect(toYaml(configuration)).toBe(
            [
                'debug: false',
                'uuid: "0e7f2a52-3b8d-4c1f-9f61-5a2c0d7c9b11"',
                'token_id: "Yq3vR8kP2mN6tX1w"',
                'token: "k9#Lm2: pQ7\\"vX4\\\\zR8 # tail"',
                'api:',
                '  host: "0.0.0.0"',
                '  port: 8080',
                '  ssl:',
                '    enabled: true',
                '    cert: "/etc/letsencrypt/live/node.example.com/fullchain.pem"',
                '    key: "/etc/letsencrypt/live/node.example.com/privkey.pem"',
                '  upload_limit: 100',
                'system:',
                '  data: "/var/lib/pterodactyl/volumes"',
                '  sftp:',
                '    bind_port: 2022',
                'allowed_mounts:',
                '  - "/mnt/shared"',
                '  - "/srv/minecraft maps"',
                'remote: "https://panel.example.com"',
            ].join('\n')
        );
    });

    it('renders empty collections inline', () => {
        expect(toYaml({ ...configuration, allowed_mounts: [] })).toContain('\nallowed_mounts: []\n');
        expect(toYaml({ nested: {}, list: [] })).toBe('nested: {}\nlist: []');
    });

    it('renders null, booleans, and numbers as YAML scalars', () => {
        expect(toYaml({ none: null, off: false, on: true, zero: 0, negative: -1.5 })).toBe(
            ['none: null', '"off": false', '"on": true', 'zero: 0', 'negative: -1.5'].join('\n')
        );
    });

    it('quotes strings that YAML would otherwise read as other types', () => {
        expect(toYaml({ values: ['true', 'null', '123', '', 'line\nbreak'] })).toBe(
            ['values:', '  - "true"', '  - "null"', '  - "123"', '  - ""', '  - "line\\nbreak"'].join('\n')
        );
    });

    it('renders sequences of mappings and nested sequences', () => {
        expect(
            toYaml({
                mounts: [
                    { source: '/mnt/a', read_only: true },
                    { source: '/mnt/b', options: ['rw', 'noexec'] },
                ],
                matrix: [
                    [1, 2],
                    [3, 4],
                ],
            })
        ).toBe(
            [
                'mounts:',
                '  - source: "/mnt/a"',
                '    read_only: true',
                '  - source: "/mnt/b"',
                '    options:',
                '      - "rw"',
                '      - "noexec"',
                'matrix:',
                '  - - 1',
                '    - 2',
                '  - - 3',
                '    - 4',
            ].join('\n')
        );
    });

    it('quotes keys that are not plain identifiers', () => {
        expect(toYaml({ 'key with space': 1, 'a:b': 2 })).toBe(['"key with space": 1', '"a:b": 2'].join('\n'));
    });
});
