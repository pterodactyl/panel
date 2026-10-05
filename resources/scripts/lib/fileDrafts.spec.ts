/** @vitest-environment jsdom */

import { afterEach, describe, expect, it, vi } from 'vitest';
import { clearNewFileDraft, readNewFileDraft, writeNewFileDraft } from './fileDrafts';

afterEach(() => {
    vi.restoreAllMocks();
    sessionStorage.clear();
});

describe('new file drafts', () => {
    it('keeps a draft per server and directory', () => {
        writeNewFileDraft('server-a', '/config', 'motd=hello');

        expect(readNewFileDraft('server-a', '/config')).toBe('motd=hello');
        expect(readNewFileDraft('server-a', '/')).toBe('');
        expect(readNewFileDraft('server-b', '/config')).toBe('');
    });

    it('removes the draft when it is cleared or emptied', () => {
        writeNewFileDraft('server-a', '/', 'draft');
        clearNewFileDraft('server-a', '/');
        expect(sessionStorage.length).toBe(0);

        writeNewFileDraft('server-a', '/', 'draft');
        writeNewFileDraft('server-a', '/', '');
        expect(sessionStorage.length).toBe(0);
    });

    it('ignores storage that is full or blocked', () => {
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new DOMException('quota', 'QuotaExceededError');
        });
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new DOMException('blocked', 'SecurityError');
        });

        expect(() => writeNewFileDraft('server-a', '/', 'draft')).not.toThrow();
        expect(readNewFileDraft('server-a', '/')).toBe('');
    });
});
