import { describe, expect, it } from 'vitest';
import { permissionGroupRows, replaceEditablePermissions, setPermissionsSelected } from './permissionSelection';

describe('extension permission selection', () => {
    it('replaces editable selections while preserving permissions the viewer cannot modify', () => {
        expect(
            replaceEditablePermissions(
                ['file.read', 'settings.reinstall', 'backup.read'],
                ['control.start'],
                new Set(['file.read', 'control.start', 'backup.read'])
            )
        ).toEqual(['settings.reinstall', 'control.start']);
    });

    it('ignores unknown permissions and permissions the viewer cannot grant', () => {
        expect(
            replaceEditablePermissions(
                [],
                ['file.read', 'settings.reinstall', '*', 'unknown.permission'],
                new Set(['file.read'])
            )
        ).toEqual(['file.read']);
    });

    it('can clear editable permissions and deduplicates selections', () => {
        expect(replaceEditablePermissions(['file.read', 'backup.read'], [], new Set(['file.read']))).toEqual([
            'backup.read',
        ]);
        expect(replaceEditablePermissions([], ['file.read', 'file.read'], new Set(['file.read']))).toEqual([
            'file.read',
        ]);
    });
});

describe('permission group rows', () => {
    it('keeps the key and description of groups whose names contain dots', () => {
        expect(permissionGroupRows('ext.votes', { view: 'View votes.', 'reset-all': 'Reset every vote.' })).toEqual([
            { permission: 'ext.votes.view', key: 'view', description: 'View votes.' },
            { permission: 'ext.votes.reset-all', key: 'reset-all', description: 'Reset every vote.' },
        ]);
    });
});

describe('permission group toggles', () => {
    it('adds only the given permissions and keeps the existing selection', () => {
        expect(setPermissionsSelected(['backup.read', 'file.read'], ['file.read', 'file.create'], true)).toEqual([
            'backup.read',
            'file.read',
            'file.create',
        ]);
    });

    it('removes only the given permissions and keeps the ones the viewer cannot edit', () => {
        expect(
            setPermissionsSelected(['file.read', 'file.delete', 'backup.read'], ['file.read', 'file.create'], false)
        ).toEqual(['file.delete', 'backup.read']);
    });
});
