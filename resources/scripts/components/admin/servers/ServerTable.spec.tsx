/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import { getServerStatus, serverColumns } from './ServerTable';
import { ServerStatusBadge } from './ServerStatusBadge';

afterEach(cleanup);

describe('ServerTable', () => {
    it.each([
        ['active', 'Active'],
        ['installing', 'Installing'],
        ['suspended', 'Suspended'],
    ] as const)('renders the %s server status', (status, label) => {
        render(<ServerStatusBadge status={status} />);

        expect(screen.getByRole('status')).toHaveTextContent(label);
    });

    it.each([
        [true, 1, 'suspended'],
        [false, 0, 'installing'],
        [false, 1, 'active'],
    ] as const)('derives %s/%s as %s', (suspended, installed, status) => {
        expect(getServerStatus(suspended, installed)).toBe(status);
    });

    it('only enables sorting for server-backed sort columns', () => {
        expect(serverColumns.filter((column) => column.enableSorting).map((column) => column.id)).toEqual([
            'name',
            'created_at',
        ]);
    });
});
