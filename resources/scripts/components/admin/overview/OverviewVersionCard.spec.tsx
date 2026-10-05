/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import type { AdminVersion } from '@/api/admin/version/queries';
import OverviewVersionCard from './OverviewVersionCard';

afterEach(cleanup);

const version: AdminVersion = {
    current: 'canary',
    latest: '1.12.0',
    is_latest: true,
    daemon: '1.11.13',
    discord: 'https://discord.gg/pterodactyl',
    donations: 'https://github.com/sponsors/pterodactyl',
};

describe('OverviewVersionCard', () => {
    it('renders the current version as a success alert', () => {
        render(<OverviewVersionCard version={version} />);

        expect(screen.getByRole('alert')).toHaveTextContent('Your panel is up-to-date.');
        expect(screen.getByRole('alert')).toHaveTextContent('You are running version canary.');
    });

    it('renders an available update as a warning alert', () => {
        render(<OverviewVersionCard version={{ ...version, is_latest: false }} />);

        expect(screen.getByRole('alert')).toHaveTextContent('A panel update is available.');
        expect(screen.getByRole('link', { name: '1.12.0' })).toHaveAttribute(
            'href',
            'https://github.com/pterodactyl/panel/releases/v1.12.0'
        );
        expect(screen.getByRole('link', { name: 'update guide' })).toHaveAttribute(
            'href',
            'https://pterodactyl.io/docs/v2/panel/updating'
        );
    });
});
