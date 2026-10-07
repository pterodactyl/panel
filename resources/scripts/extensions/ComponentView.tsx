import { useCallback, useLayoutEffect, useState, type ReactNode } from 'react';
import ExtensionMount from './ExtensionMount';
import { reportExtensionError } from './registry';
import { useComponentDecision } from './componentSession';
import type { ComponentName, ReplacementProps } from './componentTypes';

function SuspendedView({ onSuspend }: { onSuspend: () => void }) {
    useLayoutEffect(onSuspend, [onSuspend]);
    return null;
}

export default function ComponentView<TName extends ComponentName>({
    name,
    props,
    loading,
    resetKey,
}: {
    name: TName;
    props: ReplacementProps<TName>;
    loading: ReactNode;
    resetKey: string;
}) {
    const decision = useComponentDecision(name);
    const [suspended, setSuspended] = useState(false);
    const extensionId = decision.status === 'ready' ? decision.extensionId : '';
    const context = `component "${name}" (${resetKey})`;
    const onSuspend = useCallback(() => {
        reportExtensionError(
            extensionId,
            context,
            new Error('Replacement suspended outside a local Suspense boundary; using the native view.')
        );
        setSuspended(true);
    }, [extensionId, context]);
    const Default = props.Default;
    if (decision.status === 'loading') return loading;
    if (decision.status === 'default' || suspended) return <Default />;
    const Component = decision.component;
    return (
        <ExtensionMount
            extensionId={extensionId}
            context={context}
            resetKey={resetKey}
            loading={<SuspendedView onSuspend={onSuspend} />}
            failure={<Default />}
        >
            <Component {...props} />
        </ExtensionMount>
    );
}
