import { useEffect } from 'react';
import { getSlotComponents, useExtensionRegistry, type SlotProps } from '@/extensions/registry';
import ExtensionMount from '@/extensions/ExtensionMount';
import { slotMounted } from '@/extensions/slotWatch';

function slotResetKey(name: SlotProps['name'], data: SlotProps['data']): string {
    if (!data) {
        return name;
    }

    if ('resource' in data) {
        return `${data.kind}:${data.resource.attributes.id}`;
    }

    if ('directory' in data) {
        return `${data.server.attributes.uuid}:${data.directory}`;
    }

    if ('attributes' in data) {
        return data.attributes.uuid;
    }

    if ('pathname' in data) {
        return data.pathname;
    }

    return name;
}

export default function Slot({ name, data, compact }: SlotProps) {
    const registrations = useExtensionRegistry(() => getSlotComponents(name));
    const resetKey = slotResetKey(name, data);

    useEffect(() => slotMounted(name), [name]);

    return (
        <>
            {registrations.map(({ id, extensionId, component: Component }) => (
                <ExtensionMount
                    isSlot
                    key={id}
                    extensionId={extensionId}
                    context={`slot "${name}"`}
                    resetKey={resetKey}
                >
                    <Component data={data} compact={compact} />
                </ExtensionMount>
            ))}
        </>
    );
}
