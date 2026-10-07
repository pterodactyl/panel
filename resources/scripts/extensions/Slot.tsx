import { getSlotComponents, useExtensionRegistry, type SlotProps } from '@/extensions/registry';
import ExtensionMount from '@/extensions/ExtensionMount';

export default function Slot({ name, data }: SlotProps) {
    const registrations = useExtensionRegistry(() => getSlotComponents(name));
    const resetKey =
        data && 'resource' in data
            ? `${data.kind}:${data.resource.attributes.id}`
            : data && 'directory' in data
              ? `${data.server.attributes.uuid}:${data.directory}`
              : data && 'attributes' in data
                ? data.attributes.uuid
                : data && 'pathname' in data
                  ? data.pathname
                  : name;
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
                    <Component data={data} />
                </ExtensionMount>
            ))}
        </>
    );
}
