import { describe, expect, it } from 'vitest';
import { canReinstallServer, isInstallingStatus } from './serverStatus';

describe('server status', () => {
    it('treats unfinished and failed installs as installing', () => {
        expect(isInstallingStatus('installing')).toBe(true);
        expect(isInstallingStatus('install_failed')).toBe(true);
        expect(isInstallingStatus('reinstall_failed')).toBe(true);
        expect(isInstallingStatus('suspended')).toBe(false);
        expect(isInstallingStatus(null)).toBe(false);
    });

    it('only reinstalls a server that skips its install script while its install is unfinished', () => {
        expect(canReinstallServer(null, false)).toBe(true);
        expect(canReinstallServer(null, true)).toBe(false);
        expect(canReinstallServer('suspended', true)).toBe(false);
        expect(canReinstallServer('install_failed', true)).toBe(true);
        expect(canReinstallServer('reinstall_failed', true)).toBe(true);
    });
});
