import { Component, Suspense, useEffect, useMemo, type ReactNode } from 'react';
import { QueryErrorResetBoundary } from '@tanstack/react-query';
import { toast } from 'sonner';
import Button from '@/components/elements/Button';
import { clearExtensionError, ExtensionImportError, reportExtensionError } from '@/extensions/registry';
import { ExtensionContext } from '@/extensions/context';

interface BoundaryProps {
    extensionId: string;
    context: string;
    resetKey: string;
    onReset: () => void;
    fallback: (error: Error, retry: () => void) => ReactNode;
    children?: ReactNode;
}

/** Records a crash against the extension and renders the fallback until it is retried or the key changes. */
interface BoundaryState {
    error: Error | null;
}

class ExtensionBoundary extends Component<BoundaryProps, BoundaryState> {
    state: BoundaryState = { error: null };

    static getDerivedStateFromError(cause: unknown) {
        return { error: cause instanceof Error ? cause : new Error(String(cause)) };
    }

    componentDidCatch(error: Error) {
        reportExtensionError(this.props.extensionId, this.props.context, error);
    }

    componentDidUpdate(previous: BoundaryProps) {
        if (previous.resetKey !== this.props.resetKey && this.state.error) {
            this.retry();
        }
    }

    retry = () => {
        this.props.onReset();
        clearExtensionError(this.props.extensionId, this.props.context);
        this.setState({ error: null });
    };

    render() {
        return this.state.error ? this.props.fallback(this.state.error, this.retry) : this.props.children;
    }
}

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
            ? {
                  label: 'Retry extension',
                  onClick: () => {
                      for (const mount of mounts) {
                          mount.retry?.();
                      }
                  },
              }
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
        if (!notificationId) {
            return;
        }

        const mount: FailedMount = { retry };
        const mounts = failedMountsByNotification.get(notificationId) ?? new Set<FailedMount>();

        mounts.add(mount);
        failedMountsByNotification.set(notificationId, mounts);
        if (mounts.size === 1) {
            showFailureNotice(notificationId, extensionId, mounts);
        }

        return () => {
            mounts.delete(mount);
            if (mounts.size) {
                return;
            }

            failedMountsByNotification.delete(notificationId);
            toast.dismiss(notificationId);
        };
    }, [extensionId, notificationId, retry]);
    if (notificationId) {
        return null;
    }

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
                            failure === undefined ? (
                                <ExtensionFailure
                                    extensionId={extensionId}
                                    notificationId={isSlot ? failureNotificationId(extensionId, context) : undefined}
                                    retry={error instanceof ExtensionImportError ? undefined : retry}
                                />
                            ) : (
                                failure
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
