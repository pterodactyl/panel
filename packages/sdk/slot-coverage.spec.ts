import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { SLOT_NAMES } from '../../resources/scripts/extensions/registry';

const legacySlotNames = [
    'nav.items.before',
    'nav.items.after',
    'dashboard.before',
    'dashboard.after',
    'dashboard.serverRow.name.after',
    'dashboard.serverRow.metrics.after',
    'account.overview.before',
    'account.overview.after',
    'server.console.before',
    'server.console.power.after',
    'server.console.after',
    'server.files.before',
    'server.files.after',
    'panel.overview.after',
] as const;

const anchorFiles = [
    '../../resources/scripts/components/auth/LoginContainer.tsx',
    '../../resources/scripts/components/server/users/EditSubuserModal.tsx',
    '../../resources/scripts/router/routeTree.ts',
    '../../resources/scripts/router/layouts/AccountLayout.tsx',
    '../../resources/scripts/router/layouts/AdminLayout.tsx',
    '../../resources/scripts/router/layouts/ServerLayout.tsx',
    '../../resources/scripts/components/NavigationBar.tsx',
    '../../resources/scripts/components/admin/AdminSidebar.tsx',
    '../../resources/scripts/components/dashboard/DashboardContainer.tsx',
    '../../resources/scripts/components/dashboard/AccountOverviewContainer.tsx',
    '../../resources/scripts/components/dashboard/ServerRow.tsx',
    '../../resources/scripts/components/dashboard/ServerCardView.tsx',
    '../../resources/scripts/components/server/console/ServerConsoleContainer.tsx',
    '../../resources/scripts/components/server/files/FileManagerContainer.tsx',
    '../../resources/scripts/components/server/files/FileManagerView.tsx',
    '../../resources/scripts/components/server/files/FileObjectRow.tsx',
    '../../resources/scripts/components/server/files/MassActionsBar.tsx',
    '../../resources/scripts/components/server/startup/StartupContainer.tsx',
    '../../resources/scripts/components/admin/nodes/NodeDetailContainer.tsx',
    '../../resources/scripts/components/admin/servers/ServerDetailContainer.tsx',
    '../../resources/scripts/components/admin/eggs/EggDetailContainer.tsx',
    '../../resources/scripts/components/admin/users/UserDetailContainer.tsx',
    '../../resources/scripts/components/admin/overview/OverviewContainer.tsx',
    '../../resources/scripts/components/admin/users/CreateUserForm.tsx',
    '../../resources/scripts/components/admin/nodes/CreateNodeForm.tsx',
    '../../resources/scripts/components/admin/nodes/NodeSettingsTab.tsx',
    '../../resources/scripts/components/admin/servers/CreateServerForm.tsx',
    '../../resources/scripts/components/admin/servers/ServerDetailsTab.tsx',
    '../../resources/scripts/components/admin/eggs/CreateEggForm.tsx',
    '../../resources/scripts/components/admin/eggs/EggConfigurationTab.tsx',
    '../../resources/scripts/components/admin/locations/CreateLocationButton.tsx',
    '../../resources/scripts/components/admin/locations/LocationDetailContainer.tsx',
    '../../resources/scripts/components/admin/mounts/CreateMountForm.tsx',
    '../../resources/scripts/components/admin/mounts/MountDetailContainer.tsx',
    '../../resources/scripts/components/admin/databases/CreateDatabaseHostForm.tsx',
    '../../resources/scripts/components/admin/databases/DatabaseHostDetailContainer.tsx',
];

const anchorSource = anchorFiles.map((file) => readFileSync(resolve(__dirname, file), 'utf8')).join('\n');
const sdkTypes = readFileSync(resolve(__dirname, './types/extensions/registry.d.ts'), 'utf8');

const publishedSlotNames = () => {
    const declaration = sdkTypes.match(/export declare const SLOT_NAMES: readonly \[([\s\S]*?)\];/)?.[1] ?? '';

    return [...declaration.matchAll(/["']([^"']+)["']/g)].map((match) => match[1]);
};

describe('extension slot coverage', () => {
    it('keeps every original public slot name', () => {
        expect(SLOT_NAMES).toEqual(expect.arrayContaining(legacySlotNames));
    });

    it('has no duplicate public slot names', () => {
        expect(new Set(SLOT_NAMES).size).toBe(SLOT_NAMES.length);
    });

    it('publishes the exact runtime slot catalog in the SDK types', () => {
        expect(publishedSlotNames()).toEqual([...SLOT_NAMES]);
    });

    it.each(SLOT_NAMES)('mounts the %s slot in the application', (name) => {
        expect(anchorSource, `${name} is public but has no core anchor`).toContain(`'${name}'`);
    });
});
