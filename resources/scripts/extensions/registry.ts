import { lazy, useSyncExternalStore, type ComponentType, type LazyExoticComponent } from 'react';
import type { Server } from '@/api/server/types';
import type { ExtensionConfig } from '@/sdk';
import type { AppForm } from '@/components/form';
import type { UserValues } from '@/api/admin/users/types';
import type { UserData } from '@/api/account/types';
import type {
    ClientGetStartupConfigurationResponse,
    ClientFileObjectResource as FileObject,
} from '@/api/extensionTypes';
import type { ExtensionResourceContext } from './resourceContext';
import type { ExtensionTableName, ExtensionTableColumnRegistration } from './tableTypes';
import {
    isComponentName,
    isReplacementComponent,
    type ComponentName,
    type ComponentReplacement,
} from './componentTypes';

// Only type imports from application modules.

/** Every anchor a core <Slot/> renders; registering an unknown name fails at load time. */
export const SLOT_NAMES = [
    'nav.items.before',
    'nav.items.after',

    'auth.login.before',
    'auth.login.after',
    'auth.login.form.after',
    'auth.checkpoint.before',
    'auth.checkpoint.after',
    'auth.password.before',
    'auth.password.after',
    'auth.passwordReset.before',
    'auth.passwordReset.after',

    'dashboard.before',
    'dashboard.after',
    'dashboard.serverRow.before',
    'dashboard.serverRow.after',
    'dashboard.serverRow.name.after',
    'dashboard.serverRow.metrics.after',
    'account.navigation.before',
    'account.navigation.after',
    'account.overview.before',
    'account.overview.after',
    'account.api.before',
    'account.api.after',
    'account.ssh.before',
    'account.ssh.after',
    'account.activity.before',
    'account.activity.after',

    'server.navigation.before',
    'server.navigation.after',
    'server.console.before',
    'server.console.power.before',
    'server.console.power.after',
    'server.console.after',
    'server.files.before',
    'server.files.after',
    'server.files.toolbar',
    'server.files.rowActions',
    'server.files.selectionActions',
    'server.files.editor.before',
    'server.files.editor.after',
    'server.databases.before',
    'server.databases.after',
    'server.schedules.before',
    'server.schedules.after',
    'server.schedules.detail.before',
    'server.schedules.detail.after',
    'server.users.before',
    'server.users.after',
    'server.users.create.before',
    'server.users.create.after',
    'server.users.permissions.before',
    'server.backups.before',
    'server.backups.after',
    'server.network.before',
    'server.network.after',
    'server.startup.before',
    'server.startup.after',
    'server.startup.form',
    'server.settings.before',
    'server.settings.after',
    'server.activity.before',
    'server.activity.after',

    'panel.navigation.before',
    'panel.navigation.after',
    'panel.overview.before',
    'panel.overview.after',

    'panel.users.before',
    'panel.users.after',
    'panel.users.create.before',
    'panel.users.create.after',
    'panel.users.detail.before',
    'panel.users.detail.after',
    'panel.locations.before',
    'panel.locations.after',
    'panel.locations.detail.before',
    'panel.locations.detail.after',

    'panel.nodes.before',
    'panel.nodes.after',
    'panel.nodes.create.before',
    'panel.nodes.create.after',
    'panel.nodes.detail.before',
    'panel.nodes.detail.after',
    'panel.nodes.detail.actions',
    'panel.nodes.detail.about.before',
    'panel.nodes.detail.about.after',
    'panel.nodes.detail.settings.before',
    'panel.nodes.detail.settings.after',
    'panel.nodes.detail.configuration.before',
    'panel.nodes.detail.configuration.after',
    'panel.nodes.detail.allocations.before',
    'panel.nodes.detail.allocations.after',
    'panel.nodes.detail.servers.before',
    'panel.nodes.detail.servers.after',

    'panel.servers.before',
    'panel.servers.after',
    'panel.servers.create.before',
    'panel.servers.create.after',
    'panel.servers.detail.before',
    'panel.servers.detail.after',
    'panel.servers.detail.actions',
    'panel.servers.detail.about.before',
    'panel.servers.detail.about.after',
    'panel.servers.detail.details.before',
    'panel.servers.detail.details.after',
    'panel.servers.detail.build.before',
    'panel.servers.detail.build.after',
    'panel.servers.detail.startup.before',
    'panel.servers.detail.startup.after',
    'panel.servers.detail.databases.before',
    'panel.servers.detail.databases.after',
    'panel.servers.detail.mounts.before',
    'panel.servers.detail.mounts.after',
    'panel.servers.detail.manage.before',
    'panel.servers.detail.manage.after',
    'panel.servers.detail.delete.before',
    'panel.servers.detail.delete.after',

    'panel.databaseHosts.before',
    'panel.databaseHosts.after',
    'panel.databaseHosts.create.before',
    'panel.databaseHosts.create.after',
    'panel.databaseHosts.detail.before',
    'panel.databaseHosts.detail.after',
    'panel.mounts.before',
    'panel.mounts.after',
    'panel.mounts.create.before',
    'panel.mounts.create.after',
    'panel.mounts.detail.before',
    'panel.mounts.detail.after',
    'panel.eggs.before',
    'panel.eggs.after',
    'panel.eggs.create.before',
    'panel.eggs.create.after',
    'panel.eggs.detail.before',
    'panel.eggs.detail.after',
    'panel.eggs.detail.actions',
    'panel.users.detail.actions',
    'panel.users.detail.form',
    'panel.eggs.detail.configuration.before',
    'panel.eggs.detail.configuration.after',
    'panel.eggs.detail.tags.before',
    'panel.eggs.detail.tags.after',
    'panel.eggs.detail.variables.before',
    'panel.eggs.detail.variables.after',
    'panel.eggs.detail.script.before',
    'panel.eggs.detail.script.after',
    'panel.tags.before',
    'panel.tags.after',

    'panel.activity.before',
    'panel.activity.after',

    'panel.settings.before',
    'panel.settings.after',
    'panel.extensions.before',
    'panel.extensions.after',
    'panel.apiKeys.before',
    'panel.apiKeys.after',
] as const;

export type SlotName = (typeof SLOT_NAMES)[number];

export interface SubuserPermissionsSlotData {
    mode: 'create' | 'edit';
    selectedPermissions: readonly string[];
    editablePermissions: readonly string[];
    disabled: boolean;
    setPermissions(permissions: readonly string[]): void;
}

export interface RouteSlotData {
    pathname: string;
    search: unknown;
    params: Record<string, string>;
}

export interface FileManagerSlotData {
    server: Server;
    directory: string;
    files: readonly FileObject[];
    selectedFiles: readonly string[];
    isFetching: boolean;
    setSelectedFiles(names: readonly string[]): void;
    refresh(): Promise<void>;
}
export interface FileRowSlotData extends FileManagerSlotData {
    file: FileObject;
}
export interface StartupFormSlotData {
    server: Server;
    configuration: ClientGetStartupConfigurationResponse;
    isPending: boolean;
    canChangeDockerImage: boolean;
    setDockerImage(image: string): Promise<void>;
    refresh(): Promise<void>;
}
export type AdminUserFormSlotData = Extract<ExtensionResourceContext, { kind: 'admin.user' }> & {
    form: AppForm<UserValues>;
};
type FileManagerSlotName = 'server.files.toolbar' | 'server.files.selectionActions';
type ResourceActionSlotName =
    | 'panel.nodes.detail.actions'
    | 'panel.servers.detail.actions'
    | 'panel.eggs.detail.actions'
    | 'panel.users.detail.actions';
type ResourceActionSlotData<TName extends ResourceActionSlotName> = Extract<
    ExtensionResourceContext,
    {
        kind: TName extends 'panel.nodes.detail.actions'
            ? 'admin.node'
            : TName extends 'panel.servers.detail.actions'
              ? 'admin.server'
              : TName extends 'panel.eggs.detail.actions'
                ? 'admin.egg'
                : 'admin.user';
    }
>;

export type SlotComponentProps<TData = unknown> = [TData] extends [undefined] ? { data?: undefined } : { data: TData };
type ServerSlotName =
    | `dashboard.serverRow.${string}`
    | `server.navigation.${'before' | 'after'}`
    | `server.console.${string}`;
type DataLessSlotName =
    | `nav.items.${'before' | 'after'}`
    | `dashboard.${'before' | 'after'}`
    | `account.navigation.${'before' | 'after'}`
    | `account.overview.${'before' | 'after'}`
    | `server.files.${'before' | 'after'}`
    | `panel.navigation.${'before' | 'after'}`
    | `panel.overview.${'before' | 'after'}`;
export type SlotData<TName extends SlotName> = TName extends 'panel.users.detail.form'
    ? AdminUserFormSlotData
    : TName extends 'server.users.permissions.before'
      ? SubuserPermissionsSlotData
      : TName extends 'server.startup.form'
        ? StartupFormSlotData
        : TName extends FileManagerSlotName
          ? FileManagerSlotData
          : TName extends 'server.files.rowActions'
            ? FileRowSlotData
            : TName extends ResourceActionSlotName
              ? ResourceActionSlotData<TName>
              : TName extends ServerSlotName
                ? Server
                : TName extends DataLessSlotName
                  ? undefined
                  : RouteSlotData;
export type RouteSlotName = { [TName in SlotName]: SlotData<TName> extends RouteSlotData ? TName : never }[SlotName];
type DataSlotName = Exclude<SlotName, RouteSlotName>;
/** Route slots share one member, so a component can pass any route slot name it holds. */
export type SlotProps =
    | { name: RouteSlotName; data: RouteSlotData }
    | { [TName in DataSlotName]: { name: TName } & SlotComponentProps<SlotData<TName>> }[DataSlotName];

export interface SlotRegistration {
    id: number;
    extensionId: string;
    component: ComponentType<SlotComponentProps>;
}
interface BatchedSlotRegistration extends SlotRegistration {
    name: SlotName;
}
export type ScreenArea = 'account' | 'server' | 'admin';
export const SCREEN_PARENTS = ['admin.node', 'admin.server', 'admin.egg', 'admin.user'] as const;
export type ScreenParent = (typeof SCREEN_PARENTS)[number];
export type ScreenComponentProps = SlotComponentProps<RouteSlotData & { resource?: ExtensionResourceContext }>;
export type ScreenImporter = () => Promise<{ default: ComponentType<ScreenComponentProps> }>;
/** Values an egg must carry: every `all` entry and, when listed, at least one `any` entry. */
export interface ScreenMatcher {
    any?: string[];
    all?: string[];
}
/** Egg rules apply before any bundle loads; `runtime` hides the screen until the bundle's predicate answers. */
export interface ScreenCondition {
    eggFeatures?: ScreenMatcher;
    eggTags?: ScreenMatcher;
    /** How `eggFeatures` and `eggTags` combine when both are declared. Defaults to `all`. */
    match?: 'all' | 'any';
    runtime?: boolean;
}
export interface ScreenContext {
    user: UserData;
    /** The current server, on server screens. */
    server?: Server;
    /** The resource being viewed, on admin resource tabs. */
    resource?: ExtensionResourceContext;
    /** The extension's frontend configuration, as delivered to `setup`. */
    config: ExtensionConfig;
}
/** A badge value: `undefined` keeps the manifest badge, `null` or an empty string shows none. */
export type ScreenBadgeValue = string | number | null | undefined;
/** Both run inside an isolated component, so they may use hooks and suspend. */
export interface ScreenOptions {
    /** Requires `when.runtime`; the screen is listed and routable only while this returns true. */
    visible?: (context: ScreenContext) => boolean;
    /** A live badge for the navigation entry; the manifest `nav.badge` shows until it answers. */
    badge?: (context: ScreenContext) => ScreenBadgeValue;
}
export interface ExtensionScreenDefinition {
    id: string;
    area: ScreenArea;
    path: string;
    parent?: ScreenParent;
    nav?: {
        label: string;
        exact?: boolean;
        params?: Record<string, string>;
        order?: number;
        group?: string;
        badge?: string;
        /** A lucide icon name such as `life-buoy`; unknown names render the default icon. */
        icon?: string;
    };
    permission?: string[];
    when?: ScreenCondition;
}
export interface ExtensionScreenRegistration extends ExtensionScreenDefinition {
    extensionId: string;
}
export interface SiteExtensionEntry {
    id: string;
    version?: string;
    entry: string;
    /** The Tailwind prefix declared as `ui.prefix`: the extension writes `<prefix>:flex`. */
    prefix?: string | null;
    /** Changes with the extension's translation files, and is sent when loading them. */
    translations?: string | null;
    config?: ExtensionConfig;
    screens?: ExtensionScreenDefinition[];
    components?: ComponentName[];
    development?: { url: string; version: string } | null;
}
interface StagedScreen {
    importer: ScreenImporter;
    options: Readonly<ScreenOptions>;
}
export interface ExtensionRegistryBatch {
    closed: boolean;
    slots: BatchedSlotRegistration[];
    screens: Map<string, StagedScreen>;
    columns: ExtensionTableColumnRegistration[];
    components: Map<ComponentName, ComponentReplacement<ComponentName>>;
}
export interface ExtensionRuntimeState {
    id: string;
    status: 'loading' | 'loaded' | 'failed';
    error?: string;
}

const slots = new Map<SlotName, readonly SlotRegistration[]>();
const tableColumns = new Map<ExtensionTableName, readonly ExtensionTableColumnRegistration[]>();
const emptyColumns: readonly ExtensionTableColumnRegistration[] = Object.freeze([]);
const emptySlots: readonly SlotRegistration[] = Object.freeze([]);
const screens: ExtensionScreenRegistration[] = [];
export class ExtensionImportError extends Error {}
const screenImplementations = new Map<
    string,
    { component: LazyExoticComponent<ComponentType<ScreenComponentProps>>; options: Readonly<ScreenOptions> }
>();
const emptyConfig: ExtensionConfig = Object.freeze({});
const componentReplacements = new Map<ComponentName, ComponentReplacement<ComponentName>>();
const loadStates = new Map<string, ExtensionRuntimeState>();
/** Failures of mounted extension code, by extension and then by context, the latest last. */
const mountErrors = new Map<string, Map<string, string>>();
const listeners = new Set<() => void>();
let entries: readonly SiteExtensionEntry[] | undefined;
let nextSlotRegistrationId = 1;
let stateSnapshot: readonly ExtensionRuntimeState[] = [];

/** Every change notifies every listener; each getter returns the same value until its own data changes. */
export function subscribeExtensionRegistry(listener: () => void): () => void {
    listeners.add(listener);
    return () => {
        listeners.delete(listener);
    };
}
export function useExtensionRegistry<T>(read: () => T): T {
    return useSyncExternalStore(subscribeExtensionRegistry, read);
}
/** The latest failure of mounted code overrides the load state. */
function runtimeState(id: string): ExtensionRuntimeState | undefined {
    const latest = [...(mountErrors.get(id) ?? [])].at(-1);
    return latest ? Object.freeze({ id, status: 'failed', error: `${latest[0]}: ${latest[1]}` }) : loadStates.get(id);
}
function publish(): void {
    const ids = new Set([...loadStates.keys(), ...mountErrors.keys()]);
    stateSnapshot = Object.freeze([...ids].flatMap((id) => runtimeState(id) ?? []));
    listeners.forEach((listener) => listener());
}
export function getExtensionLoadState(id: string): ExtensionRuntimeState | undefined {
    return loadStates.get(id);
}
export function getScreenComponent(
    extensionId: string,
    screenId: string
): LazyExoticComponent<ComponentType<ScreenComponentProps>> | undefined {
    return screenImplementations.get(`${extensionId}:${screenId}`)?.component;
}
/** Undefined until the screen's bundle has loaded. */
export function getScreenOptions(extensionId: string, screenId: string): Readonly<ScreenOptions> | undefined {
    return screenImplementations.get(`${extensionId}:${screenId}`)?.options;
}
export function getExtensionConfig(extensionId: string): ExtensionConfig {
    return entries?.find((entry) => entry.id === extensionId)?.config ?? emptyConfig;
}
export function getLoadableExtensions(): readonly SiteExtensionEntry[] | undefined {
    return entries;
}

export function getComponentOwner(name: ComponentName): string | undefined {
    return entries?.find((entry) => entry.components?.includes(name))?.id;
}

export function getComponentReplacement(name: ComponentName): ComponentReplacement<ComponentName> | undefined {
    return componentReplacements.get(name);
}

export function getExtensionStates(): readonly ExtensionRuntimeState[] {
    return stateSnapshot;
}
export function setExtensionState(state: ExtensionRuntimeState): void {
    loadStates.set(state.id, Object.freeze({ ...state }));
    publish();
}
/** Records a failure, unless the same one is already recorded for the context; returns its message. */
function recordError(extensionId: string, context: string, cause: unknown): string | undefined {
    const message = cause instanceof Error ? cause.message : String(cause);
    const errors = mountErrors.get(extensionId) ?? new Map<string, string>();
    if (errors.get(context) === message) return undefined;
    console.error(`[extensions] "${extensionId}" failed in ${context}:`, cause);
    errors.delete(context);
    errors.set(context, message);
    mountErrors.set(extensionId, errors);
    return message;
}
/** Repeat reports of the same failure in a context are ignored. */
export function reportExtensionError(extensionId: string, context: string, cause: unknown): void {
    if (recordError(extensionId, context, cause) !== undefined) publish();
}
export function failExtensionLoad(id: string, context: string, cause: unknown): void {
    const message = recordError(id, context, cause) ?? mountErrors.get(id)!.get(context)!;
    loadStates.set(id, Object.freeze({ id, status: 'failed', error: `${context}: ${message}` }));
    publish();
}

/** Restrict extension paths to static segments and named parameters, with a static namespace. */
function normalizeScreenPath(path: string): string {
    const normalized = path.replace(/\/+$/, '');
    if (!/^[a-z][a-z0-9-]*(?:\/(?:[a-z][a-z0-9-]*|\$[a-zA-Z][a-zA-Z0-9_]*))*$/.test(normalized)) {
        throw new Error(`Invalid screen path "${path}"`);
    }
    const parameters = normalized.split('/').filter((segment) => segment.startsWith('$'));
    if (parameters.includes('$id')) throw new Error('The id path parameter is reserved for the panel.');
    if (new Set(parameters).size !== parameters.length) throw new Error(`Duplicate path parameter in "${path}"`);
    return normalized;
}

export function resolveScreenPath(path: string, params: Record<string, string> = {}): string {
    return path.replace(/\$([a-zA-Z][a-zA-Z0-9_]*)/g, (_match, name: string) => {
        const value = params[name];
        if (!value) throw new Error(`Missing navigation parameter "${name}".`);
        return encodeURIComponent(value);
    });
}
function matchesValues(matcher: ScreenMatcher, values: readonly string[]): boolean {
    const present = new Set(values.map((value) => value.toLowerCase()));
    const has = (value: string) => present.has(value.toLowerCase());
    return (matcher.all ?? []).every(has) && (!matcher.any || matcher.any.some(has));
}
/** A rule never matches without a server to test. */
export function matchesScreenCondition(when: ScreenCondition | undefined, server: Server | undefined): boolean {
    const results: boolean[] = [];
    if (when?.eggFeatures) results.push(matchesValues(when.eggFeatures, server?.attributes.egg_features ?? []));
    if (when?.eggTags) results.push(matchesValues(when.eggTags, server?.attributes.egg_tags ?? []));
    return when?.match === 'any' ? results.some(Boolean) : results.every(Boolean);
}
export function prepareExtensions(
    advertised: readonly SiteExtensionEntry[],
    corePaths: Record<ScreenArea, readonly string[]> = { account: [], server: [], admin: [] },
    resourcePaths: Partial<Record<ScreenParent, readonly string[]>> = {}
): readonly SiteExtensionEntry[] {
    if (entries) return entries;
    const accepted: SiteExtensionEntry[] = [];
    const claimed = new Map<string, string>();
    const seenIds = new Set<string>();
    const componentClaims = new Map<string, Set<string>>();
    for (const entry of advertised) {
        for (const name of entry.components ?? []) {
            const owners = componentClaims.get(name) ?? new Set<string>();
            owners.add(entry.id);
            componentClaims.set(name, owners);
        }
    }
    for (const entry of advertised) {
        if (seenIds.has(entry.id)) continue;
        seenIds.add(entry.id);
        try {
            const names = entry.components ?? [];
            if (new Set(names).size !== names.length || names.some((name) => !isComponentName(name))) {
                throw new Error('Invalid or duplicate component replacement name.');
            }
            for (const name of names) {
                if (componentClaims.get(name)!.size > 1) {
                    throw new Error(
                        `Component "${name}" is declared by competing extensions: ${[...componentClaims.get(name)!].sort().join(', ')}.`
                    );
                }
            }
            const ids = new Set<string>();
            const paths = new Map<string, string>();
            const pending = (entry.screens ?? []).map((screen) => {
                if (!/^[a-z][a-z0-9-]*$/.test(screen.id) || ids.has(screen.id))
                    throw new Error(`Invalid or duplicate screen id "${screen.id}"`);
                ids.add(screen.id);
                if (!Object.hasOwn(corePaths, screen.area)) throw new Error(`Invalid screen area "${screen.area}"`);
                const path = normalizeScreenPath(screen.path);
                if (screen.nav) resolveScreenPath(path, screen.nav.params);
                const first = path.split('/')[0];
                if (screen.parent && (screen.area !== 'admin' || !SCREEN_PARENTS.includes(screen.parent))) {
                    throw new Error(`Invalid screen parent "${screen.parent}"`);
                }
                const reserved = screen.parent ? (resourcePaths[screen.parent] ?? []) : corePaths[screen.area];
                if (reserved.some((core) => core.replace(/^\//, '').split('/')[0] === first)) {
                    throw new Error(`screen "${path}" collides with core in ${screen.area}`);
                }
                if (screen.area !== 'server' && (screen.when?.eggFeatures || screen.when?.eggTags)) {
                    throw new Error(`screen "${screen.id}" declares egg rules outside the server area`);
                }
                const key = `${screen.parent ?? screen.area}:${path.replace(/\$[a-zA-Z][a-zA-Z0-9_]*/g, '$param')}`;
                const owner = claimed.get(key) ?? paths.get(key);
                if (owner) throw new Error(`screen "${path}" collides with extension "${owner}"`);
                paths.set(key, entry.id);
                return Object.freeze({
                    id: screen.id,
                    area: screen.area,
                    path,
                    parent: screen.parent,
                    nav: screen.nav,
                    permission: screen.permission,
                    when: screen.when,
                    extensionId: entry.id,
                });
            });
            paths.forEach((owner, key) => claimed.set(key, owner));
            screens.push(...pending);
            accepted.push(entry);
            setExtensionState({ id: entry.id, status: 'loading' });
        } catch (error) {
            failExtensionLoad(entry.id, 'metadata', error);
        }
    }
    entries = Object.freeze(accepted);
    return entries;
}

function assertBatchOpen(batch: ExtensionRegistryBatch): void {
    if (batch.closed) throw new Error('Extension registration batch is closed.');
}
export function createExtensionRegistryBatch(): ExtensionRegistryBatch {
    return { closed: false, slots: [], screens: new Map(), columns: [], components: new Map() };
}
/** Keeps registrations in the order the panel advertises their extensions; the sort is stable. */
function mergeByEntryOrder<TKey, TItem extends { extensionId: string }>(
    map: Map<TKey, readonly TItem[]>,
    key: TKey,
    added: readonly TItem[]
): void {
    const order = (id: string) => entries?.findIndex((entry) => entry.id === id) ?? 0;
    map.set(
        key,
        Object.freeze([...(map.get(key) ?? []), ...added].sort((a, b) => order(a.extensionId) - order(b.extensionId)))
    );
}

export function commitExtensionRegistryBatch(extensionId: string, batch: ExtensionRegistryBatch): void {
    assertBatchOpen(batch);
    for (const screen of screens.filter((screen) => screen.extensionId === extensionId)) {
        const staged = batch.screens.get(screen.id);
        if (!staged) throw new Error(`Missing implementation for screen "${screen.id}"`);
        if (screen.when?.runtime && !staged.options.visible) {
            throw new Error(`Missing visibility predicate for screen "${screen.id}"`);
        }
    }
    for (const name of entries?.find((entry) => entry.id === extensionId)?.components ?? []) {
        if (!batch.components.has(name)) throw new Error(`Missing implementation for component "${name}"`);
    }
    batch.closed = true;
    for (const name of new Set(batch.slots.map((slot) => slot.name))) {
        mergeByEntryOrder(
            slots,
            name,
            batch.slots.filter((slot) => slot.name === name)
        );
    }
    for (const name of new Set(batch.columns.map((column) => column.name))) {
        mergeByEntryOrder(
            tableColumns,
            name,
            batch.columns.filter((column) => column.name === name)
        );
    }
    batch.screens.forEach(({ importer, options }, id) =>
        screenImplementations.set(`${extensionId}:${id}`, {
            component: lazy(() =>
                importer().catch((cause: unknown) => {
                    throw new ExtensionImportError('Unable to load extension screen', { cause });
                })
            ),
            options,
        })
    );
    batch.components.forEach((replacement, name) => componentReplacements.set(name, replacement));
    loadStates.set(extensionId, Object.freeze({ id: extensionId, status: 'loaded' }));
    publish();
}

export function registerComponentReplacement<TName extends ComponentName>(
    extensionId: string,
    name: TName,
    replacement: ComponentReplacement<TName>,
    batch: ExtensionRegistryBatch
): void {
    assertBatchOpen(batch);
    if (!isComponentName(name) || getComponentOwner(name) !== extensionId) {
        throw new Error(`Component "${name}" must be declared in this extension's ui.components.`);
    }
    if (batch.components.has(name)) throw new Error(`Duplicate implementation for component "${name}".`);
    if (
        !isReplacementComponent(replacement) &&
        !(replacement !== null && 'load' in replacement && replacement.load instanceof Function)
    ) {
        throw new Error(`Invalid implementation for component "${name}". Use a component or a lazy importer.`);
    }
    batch.components.set(name, replacement);
}
export function abortExtensionRegistryBatch(batch: ExtensionRegistryBatch): void {
    assertBatchOpen(batch);
    batch.closed = true;
}
export function registerSlotComponent(
    extensionId: string,
    name: SlotName,
    component: ComponentType<SlotComponentProps>,
    batch: ExtensionRegistryBatch
): void {
    if (!SLOT_NAMES.includes(name)) throw new Error(`Unknown slot "${name}"`);
    assertBatchOpen(batch);
    batch.slots.push(Object.freeze({ id: nextSlotRegistrationId++, extensionId, name, component }));
}
export function getSlotComponents(name: SlotName): readonly SlotRegistration[] {
    return slots.get(name) ?? emptySlots;
}
export function registerExtensionTableColumn(
    column: ExtensionTableColumnRegistration,
    batch: ExtensionRegistryBatch
): void {
    assertBatchOpen(batch);
    if (!['admin.nodes', 'admin.servers', 'admin.eggs'].includes(column.name))
        throw new Error(`Unknown extension table "${column.name}".`);
    if (!/^[a-z][a-z0-9-]{0,47}$/.test(column.id) || !column.label.trim())
        throw new Error('Table columns require a valid id and label.');
    if (batch.columns.some((existing) => existing.name === column.name && existing.id === column.id))
        throw new Error(`Duplicate table column "${column.id}".`);
    batch.columns.push(Object.freeze({ ...column }));
}
export function getExtensionTableColumns(name: ExtensionTableName): readonly ExtensionTableColumnRegistration[] {
    return tableColumns.get(name) ?? emptyColumns;
}
export function registerScreen(
    extensionId: string,
    id: string,
    component: ScreenImporter,
    batch: ExtensionRegistryBatch,
    options: ScreenOptions = {}
): void {
    assertBatchOpen(batch);
    const screen = screens.find((screen) => screen.extensionId === extensionId && screen.id === id);
    if (!screen) throw new Error(`Undeclared screen "${id}"`);
    if (batch.screens.has(id)) throw new Error(`Duplicate implementation for screen "${id}"`);
    const { visible, badge } = options;
    if ((visible && !(visible instanceof Function)) || (badge && !(badge instanceof Function))) {
        throw new Error(`Screen "${id}" options must be functions.`);
    }
    if (visible && !screen.when?.runtime) {
        throw new Error(`Screen "${id}" must declare "when": { "runtime": true } to register a visibility predicate.`);
    }
    batch.screens.set(id, { importer: component, options: Object.freeze({ visible, badge }) });
}
export function getExtensionScreens(area: ScreenArea, parent?: ScreenParent): readonly ExtensionScreenRegistration[] {
    return screens
        .filter((screen) => screen.area === area && screen.parent === parent)
        .sort((a, b) => (a.nav?.order ?? 0) - (b.nav?.order ?? 0));
}
export function clearExtensionError(extensionId: string, context: string): void {
    const errors = mountErrors.get(extensionId);
    if (!errors?.delete(context)) return;
    if (!errors.size) mountErrors.delete(extensionId);
    publish();
}
