import { Suspense, useEffect, useMemo, type ReactNode } from 'react';
import { QueryErrorResetBoundary } from '@tanstack/react-query';
import { toast } from 'sonner';
import Button from '@/components/elements/Button';
import ExtensionBoundary from '@/extensions/ExtensionBoundary';
import { ExtensionImportError } from '@/extensions/registry';
import { ExtensionContext } from '@/extensions/context';

interface FailedMount {
    retry?: () => void;
}

const failedMountsByNotification = new Map<string, Set<FailedMount>>();

const failureNotificationId = (extensionId: string, context: string) => `extension-failure:${extensionId}:${context}`;

function showFailureNotice(notificationId: string, extensionId: string, mounts: Set<FailedMount>) {
    const retryable = [...mounts].some((mount) => mount.retry);
    toast.error(`The "${extensionId}" extension could not display this content.`, {
        id: notificationId,
        duration: Infinity,
        action: retryable
            ? { label: 'Retry extension', onClick: () => [...mounts].forEach((mount) => mount.retry?.()) }
            : { label: 'Reload page', onClick: () => window.location.reload() },
        description: retryable ? (
            <Button type='button' onClick={() => window.location.reload()}>
                Reload page
            </Button>
        ) : undefined,
    });
}

/** Mounts sharing a `notificationId` share one toast whose retry resets all of them. */
export function ExtensionFailure({
    extensionId,
    retry,
    notificationId,
}: {
    extensionId: string;
    retry?: () => void;
    notificationId?: string;
}) {
    useEffect(() => {
        if (!notificationId) return;
        const mount: FailedMount = { retry };
        const mounts = failedMountsByNotification.get(notificationId) ?? new Set<FailedMount>();
        mounts.add(mount);
        failedMountsByNotification.set(notificationId, mounts);
        if (mounts.size === 1) showFailureNotice(notificationId, extensionId, mounts);
        return () => {
            mounts.delete(mount);
            if (mounts.size) return;
            failedMountsByNotification.delete(notificationId);
            toast.dismiss(notificationId);
        };
    }, [extensionId, notificationId, retry]);
    if (notificationId) return null;

    return (
        <div role='alert'>
            <p>The "{extensionId}" extension could not display this content.</p>
            {retry && (
                <Button type='button' onClick={retry}>
                    Retry extension
                </Button>
            )}
            <Button type='button' onClick={() => window.location.reload()}>
                Reload page
            </Button>
        </div>
    );
}

export default function ExtensionMount({
    extensionId,
    context,
    resetKey,
    loading = null,
    isSlot = false,
    failure,
    children,
}: {
    extensionId: string;
    context: string;
    resetKey: string;
    loading?: ReactNode;
    isSlot?: boolean;
    /** Rendered instead of the failure notice. */
    failure?: ReactNode;
    children: ReactNode;
}) {
    const mount = useMemo(() => ({ extensionId, context }), [extensionId, context]);
    return (
        <ExtensionContext.Provider value={mount}>
            <QueryErrorResetBoundary>
                {({ reset }) => (
                    <ExtensionBoundary
                        {...mount}
                        resetKey={resetKey}
                        onReset={reset}
                        fallback={(error, retry) =>
                            failure !== undefined ? (
                                failure
                            ) : (
                                <ExtensionFailure
                                    extensionId={extensionId}
                                    notificationId={isSlot ? failureNotificationId(extensionId, context) : undefined}
                                    retry={error instanceof ExtensionImportError ? undefined : retry}
                                />
                            )
                        }
                    >
                        <Suspense fallback={loading}>{children}</Suspense>
                    </ExtensionBoundary>
                )}
            </QueryErrorResetBoundary>
        </ExtensionContext.Provider>
    );
}
