import { Button, Switch, TitledGreyBox, type SubuserPermissionsSlotData } from '@pterodactyl/sdk';

const presets = [
    { name: 'Viewer', permissions: ['websocket.connect', 'file.read', 'backup.read', 'activity.read'] },
    {
        name: 'Operator',
        permissions: [
            'websocket.connect',
            'control.console',
            'control.start',
            'control.stop',
            'control.restart',
            'activity.read',
        ],
    },
    {
        name: 'File manager',
        permissions: [
            'websocket.connect',
            'file.read',
            'file.read-content',
            'file.create',
            'file.update',
            'file.delete',
            'file.archive',
        ],
    },
] as const;

const reinstallPermission = 'settings.reinstall';

export default function SubuserPresets({ data }: { data: SubuserPermissionsSlotData }) {
    const canChangeReinstall = !data.disabled && data.editablePermissions.includes(reinstallPermission);

    const setReinstall = (enabled: boolean) => {
        const permissions = data.selectedPermissions.filter((permission) => permission !== reinstallPermission);
        data.setPermissions(enabled ? [...permissions, reinstallPermission] : permissions);
    };

    return (
        <TitledGreyBox title={'Permission shortcuts'} className={'hw:mt-6'}>
            <div style={{ display: 'grid', gap: '1rem' }}>
                <p>
                    Choose a starting point, then adjust the checkboxes below. Presets replace permissions you can edit.
                </p>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem' }}>
                    {presets.map((preset) => (
                        <Button
                            key={preset.name}
                            type={'button'}
                            disabled={data.disabled}
                            onClick={() => data.setPermissions(preset.permissions)}
                        >
                            {preset.name}
                        </Button>
                    ))}
                </div>
                <Switch
                    label={'Allow this subuser to reinstall the server'}
                    checked={data.selectedPermissions.includes(reinstallPermission)}
                    disabled={!canChangeReinstall}
                    onChange={setReinstall}
                />
                <p>
                    This only selects the reinstall permission; it does not reinstall anything. Changes apply when you{' '}
                    {data.mode === 'create' ? 'invite the user' : 'save'}.
                </p>
            </div>
        </TitledGreyBox>
    );
}
