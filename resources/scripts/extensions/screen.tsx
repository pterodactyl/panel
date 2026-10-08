import { type FunctionComponent } from 'react';
import { useLocation } from '@tanstack/react-router';
import Spinner from '@/components/elements/Spinner';
import { NotFound } from '@/components/elements/ScreenBlock';
import PermissionRoute from '@/components/elements/PermissionRoute';
import ExtensionMount, { ExtensionFailure } from '@/extensions/ExtensionMount';
import { canRetryExtension, retryExtension } from '@/extensions/loader';
import { useRouteSlotData } from '@/router/routeSlots';
import { useCurrentResource } from './resourceContext';
import { ScreenGate } from './screenNavigation';
import {
    getExtensionLoadState,
    getScreenComponent,
    useExtensionRegistry,
    type ExtensionScreenRegistration,
    type ScreenArea,
} from '@/extensions/registry';

export function extensionScreenComponent(
    registration: ExtensionScreenRegistration,
    area: ScreenArea
): FunctionComponent {
    const { extensionId, id } = registration;

    function ScreenImplementation() {
        const state = useExtensionRegistry(() => getExtensionLoadState(extensionId));
        const data = useRouteSlotData();
        const resource = useCurrentResource();
        const Lazy = getScreenComponent(extensionId, id);

        if (state?.status === 'failed') {
            return (
                <ExtensionFailure
                    extensionId={extensionId}
                    retry={canRetryExtension(extensionId) ? () => void retryExtension(extensionId) : undefined}
                />
            );
        }

        return Lazy ? <Lazy data={{ ...data, resource: resource ?? undefined }} /> : <Spinner centered />;
    }

    return function ExtensionScreen() {
        const pathname = useLocation({ select: (location) => location.pathname });
        const screen = (
            <ExtensionMount
                extensionId={extensionId}
                context={`screen "${id}"`}
                resetKey={pathname}
                loading={<Spinner centered />}
            >
                <ScreenImplementation />
            </ExtensionMount>
        );

        return (
            <ScreenGate screen={registration} pending={<Spinner centered />} hidden={<NotFound />}>
                {area === 'server' && registration.permission ? (
                    <PermissionRoute permission={registration.permission}>{screen}</PermissionRoute>
                ) : (
                    screen
                )}
            </ScreenGate>
        );
    };
}
