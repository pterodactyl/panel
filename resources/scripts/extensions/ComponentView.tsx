import { Suspense, useLayoutEffect, useMemo, useState, type ReactNode } from 'react';
import ExtensionBoundary from './ExtensionBoundary';
import { ExtensionContext } from './context';
import { reportExtensionError } from './registry';
import { useComponentDecision } from './componentSession';
import type { ComponentName, ReplacementProps } from './componentTypes';

const noReset = () => {};
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
    const mount = useMemo(
        () => ({
            extensionId: decision.status === 'ready' ? decision.extensionId : '',
            context: `component "${name}" (${resetKey})`,
        }),
        [decision, name, resetKey]
    );
    const onSuspend = useMemo(
        () => () => {
            reportExtensionError(
                mount.extensionId,
                mount.context,
                new Error('Replacement suspended outside a local Suspense boundary; using the native view.')
            );
            setSuspended(true);
        },
        [mount]
    );
    const Default = props.Default;
    if (decision.status === 'loading') return loading;
    if (decision.status === 'default' || suspended) return <Default />;
    const Component = decision.component;
    return (
        <ExtensionContext.Provider value={mount}>
            <ExtensionBoundary {...mount} resetKey={resetKey} onReset={noReset} fallback={() => <Default />}>
                <Suspense fallback={<SuspendedView onSuspend={onSuspend} />}>
                    <Component {...props} />
                </Suspense>
            </ExtensionBoundary>
        </ExtensionContext.Provider>
    );
}
