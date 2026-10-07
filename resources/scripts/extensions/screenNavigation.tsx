import { useMemo, type ReactNode } from 'react';
import { useLocation } from '@tanstack/react-router';
import { useCurrentUser } from '@/api/account/queries';
import { useServerQuery } from '@/api/server/queries';
import { useServerRouteId } from '@/router/params';
import ExtensionMount from '@/extensions/ExtensionMount';
import { useCurrentResource } from '@/extensions/resourceContext';
import {
    getExtensionConfig,
    getExtensionLoadState,
    getScreenOptions,
    matchesScreenCondition,
    useExtensionRegistry,
    type ExtensionScreenRegistration,
    type ScreenContext,
    type ScreenOptions,
} from '@/extensions/registry';

type Screen = ExtensionScreenRegistration;

/** Only server-area screens see the current server. */
function useAreaServer(screen: Screen) {
    const id = useServerRouteId();
    return useServerQuery(screen.area === 'server' ? (id ?? '') : '').data;
}

function useScreenContext(screen: Screen): ScreenContext {
    const user = useCurrentUser();
    const server = useAreaServer(screen);
    const resource = useCurrentResource() ?? undefined;
    const config = getExtensionConfig(screen.extensionId);
    return useMemo(() => ({ user, server, resource, config }), [user, server, resource, config]);
}

function Predicate({
    screen,
    visible,
    hidden,
    children,
}: {
    screen: Screen;
    visible: NonNullable<ScreenOptions['visible']>;
    hidden: ReactNode;
    children: ReactNode;
}) {
    return visible(useScreenContext(screen)) === true ? children : hidden;
}

function ConditionalScreen({
    screen,
    pending,
    hidden,
    children,
}: {
    screen: Screen;
    pending: ReactNode;
    hidden: ReactNode;
    children: ReactNode;
}) {
    const { extensionId, id, when } = screen;
    const server = useAreaServer(screen);
    const pathname = useLocation({ select: (location) => location.pathname });
    const visible = useExtensionRegistry(() => getScreenOptions(extensionId, id)?.visible);
    const failed = useExtensionRegistry(() => getExtensionLoadState(extensionId)?.status === 'failed');

    if (!matchesScreenCondition(when, server)) return hidden;
    if (!when?.runtime) return children;
    if (!visible) return failed ? hidden : pending;
    return (
        <ExtensionMount
            extensionId={extensionId}
            context={`screen "${id}" visibility`}
            resetKey={pathname}
            loading={pending}
            failure={hidden}
        >
            <Predicate screen={screen} visible={visible} hidden={hidden}>
                {children}
            </Predicate>
        </ExtensionMount>
    );
}

/** Renders `children` while the screen's conditions hold, `pending` while its predicate can't answer yet. */
export function ScreenGate({
    screen,
    pending = null,
    hidden = null,
    children,
}: {
    screen?: Screen;
    pending?: ReactNode;
    hidden?: ReactNode;
    children: ReactNode;
}) {
    return screen?.when ? (
        <ConditionalScreen screen={screen} pending={pending} hidden={hidden}>
            {children}
        </ConditionalScreen>
    ) : (
        children
    );
}

const BADGE_MAX_LENGTH = 32;

function Badge({ children }: { children: string }) {
    return <span className={'rounded bg-muted px-1 text-xs text-muted-foreground'}>{children}</span>;
}

function LiveBadge({
    screen,
    badge,
    fallback,
}: {
    screen: Screen;
    badge: NonNullable<ScreenOptions['badge']>;
    fallback: ReactNode;
}) {
    const value = badge(useScreenContext(screen));
    if (value === undefined) return fallback;
    const text = value === null ? '' : String(value).slice(0, BADGE_MAX_LENGTH);
    return text ? <Badge>{text}</Badge> : null;
}

function RegisteredBadge({ screen, fallback }: { screen: Screen; fallback: ReactNode }) {
    const { extensionId, id } = screen;
    const pathname = useLocation({ select: (location) => location.pathname });
    const badge = useExtensionRegistry(() => getScreenOptions(extensionId, id)?.badge);
    if (!badge) return fallback;
    return (
        <ExtensionMount
            extensionId={extensionId}
            context={`screen "${id}" badge`}
            resetKey={pathname}
            loading={fallback}
            failure={fallback}
        >
            <LiveBadge screen={screen} badge={badge} fallback={fallback} />
        </ExtensionMount>
    );
}

/** The extension's live badge once it answers, else the manifest's. */
export function ScreenBadge({ screen, badge }: { screen?: Screen; badge?: string }) {
    const fallback = badge ? <Badge>{badge}</Badge> : null;
    return screen ? <RegisteredBadge screen={screen} fallback={fallback} /> : fallback;
}
