// @vitest-environment jsdom

import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import ExportEggButton from '@/components/admin/eggs/ExportEggButton';

const mocks = vi.hoisted(() => ({
    refetch: vi.fn(),
    notifyError: vi.fn(),
}));

vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));
vi.mock('@/api/admin/eggs/queries', () => ({
    useExportAdminEgg: () => ({ refetch: mocks.refetch, isFetching: false }),
}));

// The export endpoint is requested with `responseType: 'text'`, so the query hands back the
// raw egg file exactly as the panel wrote it.
const exported = JSON.stringify(
    {
        _comment: 'DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PTERODACTYL PANEL - PTERODACTYL.IO',
        meta: { version: 'PTDL_v2', update_url: null },
        name: 'Paper',
        config: { files: '{}', startup: '{}', logs: '{}', stop: 'stop' },
        scripts: { installation: { script: 'echo hi', container: 'alpine', entrypoint: 'ash' } },
        variables: [],
    },
    null,
    4
);

const egg = { attributes: { id: 3, name: 'Paper' } } as AdminEgg;

describe('ExportEggButton', () => {
    let blobs: Blob[];

    beforeEach(() => {
        blobs = [];
        mocks.refetch.mockResolvedValue({ data: exported });
        window.URL.createObjectURL = vi.fn((blob: Blob) => {
            blobs.push(blob);

            return 'blob:egg';
        });
        window.URL.revokeObjectURL = vi.fn();
        vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('downloads the exported file as-is so it can be imported again', async () => {
        render(<ExportEggButton egg={egg} />);

        fireEvent.click(screen.getByRole('button', { name: 'Export' }));

        await waitFor(() => expect(blobs).toHaveLength(1));

        // jsdom's Blob has no text(), so read it back with a FileReader.
        const contents = await new Promise<string>((resolve) => {
            const reader = new FileReader();

            reader.onload = () => resolve(reader.result as string);
            reader.readAsText(blobs[0]);
        });

        expect(contents).toBe(exported);
        expect(JSON.parse(contents)).toMatchObject({ meta: { version: 'PTDL_v2' }, name: 'Paper' });
        expect(mocks.notifyError).not.toHaveBeenCalled();
    });
});
