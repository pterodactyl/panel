import { getBootstrapExtensions } from '@/bootstrap';
import type { ExtensionDefinition, ExtensionSetupContext } from '@/sdk';
import { isObject } from '@/lib/objects';
import { registerClassPrefixes } from '@/lib/cn';
import {
    abortExtensionRegistryBatch,
    commitExtensionRegistryBatch,
    createExtensionRegistryBatch,
    registerScreen,
    registerSlotComponent,
    registerExtensionTableColumn,
    registerComponentReplacement,
    failExtensionLoad,
    clearExtensionError,
    setExtensionState,
    prepareExtensions,
    getLoadableExtensions,
    type ExtensionRegistryBatch,
} from '@/extensions/registry';

export type { SiteExtensionEntry } from '@/extensions/registry';
import type { ExtensionTableColumnRegistration } from './tableTypes';
import type { SiteExtensionEntry } from '@/extensions/registry';
import { startExtensionDevelopmentReload } from './development';

export const EXTENSION_IMPORT_TIMEOUT_MS = 15_000;

const isThenable = <T>(value: T): value is T & PromiseLike<void> =>
    isObject(value) && 'then' in value && value.then instanceof Function;

const withTimeout = async <T>(promise: Promise<T>): Promise<T> => {
    let timeout: ReturnType<typeof setTimeout> | undefined;
    const timer = new Promise<never>((_, reject) => {
        timeout = setTimeout(() => {
            reject(new Error(`bundle import exceeded ${EXTENSION_IMPORT_TIMEOUT_MS}ms`));
        }, EXTENSION_IMPORT_TIMEOUT_MS);
    });

    try {
        return await Promise.race([promise, timer]);
    } finally {
        if (timeout !== undefined) {
            clearTimeout(timeout);
        }
    }
};

const createContext = (entry: SiteExtensionEntry, batch: ExtensionRegistryBatch): ExtensionSetupContext => ({
    meta: { id: entry.id, version: entry.version },
    config: entry.config ?? {},
    components: {
        replace: (name, replacement) => registerComponentReplacement(entry.id, name, replacement, batch),
    },
    slots: {
        register: (name, component) =>
            registerSlotComponent(entry.id, name, component as Parameters<typeof registerSlotComponent>[2], batch),
    },
    screens: {
        register: (id, component, options) => registerScreen(entry.id, id, component, batch, options),
    },
    columns: {
        register: (name, column) =>
            registerExtensionTableColumn(
                {
                    ...column,
                    name,
                    extensionId: entry.id,
                    component: column.component as ExtensionTableColumnRegistration['component'],
                },
                batch
            ),
    },
});

export type ExtensionModule = object;
type ModuleImporter = (url: string) => Promise<ExtensionModule>;
const nativeImport: ModuleImporter = (url) => import(/* @vite-ignore */ url);

/** Rewrites the browser's missing-export error into an actionable SDK version message. */
const describeBootError = (cause: unknown): Error => {
    const error = cause instanceof Error ? cause : new Error(String(cause));
    const message = error.message;
    const match = message.match(/does not provide an export named '([^']+)'/);
    if (!match) {
        return error;
    }

    return new Error(
        `this extension needs an SDK export ('${match[1]}') that the panel's current frontend build does not provide - ` +
            'update the panel, or rebuild its frontend if it is older than the installed panel version'
    );
};

const parseExtensionDefinition = <T extends object>(module: T): ExtensionDefinition => {
    if (!('default' in module) || !isObject(module.default) || !('setup' in module.default)) {
        throw new Error('bundle has no default export with a setup() function');
    }

    if (!(module.default.setup instanceof Function)) {
        throw new Error('bundle has no default export with a setup() function');
    }

    return module.default as ExtensionDefinition;
};

let loading: Promise<void> | undefined;
let importBundle: ModuleImporter = nativeImport;
const failedImports = new Set<string>();
const retries = new Map<string, Promise<void>>();

async function bootExtension(entry: SiteExtensionEntry): Promise<void> {
    let module: ExtensionModule;
    try {
        module = await withTimeout(importBundle(entry.entry));
    } catch (cause) {
        failedImports.add(entry.id);
        failExtensionLoad(entry.id, 'boot', describeBootError(cause));
        return;
    }

    const batch = createExtensionRegistryBatch();
    try {
        const definition = parseExtensionDefinition(module);
        const setupResult = definition.setup(createContext(entry, batch));
        if (isThenable(setupResult)) {
            void Promise.resolve(setupResult).catch(() => {});
            throw new Error('setup() must register synchronously and must not return a promise');
        }
        commitExtensionRegistryBatch(entry.id, batch);
    } catch (cause) {
        if (!batch.closed) abortExtensionRegistryBatch(batch);
        failExtensionLoad(entry.id, 'boot', describeBootError(cause));
    }
}

/** Call once after the core mounts. */
export function loadExtensions(importModule: ModuleImporter = nativeImport): Promise<void> {
    if (loading) return loading;
    importBundle = importModule;
    if (!getLoadableExtensions()) prepareExtensions(getBootstrapExtensions());
    registerClassPrefixes((getLoadableExtensions() ?? []).map((entry) => entry.prefix));
    startExtensionDevelopmentReload(getLoadableExtensions() ?? []);
    loading = Promise.all((getLoadableExtensions() ?? []).map(bootExtension)).then(() => {});
    return loading;
}

export function canRetryExtension(id: string): boolean {
    return failedImports.has(id);
}

export function retryExtension(id: string): Promise<void> {
    const pending = retries.get(id);
    if (pending) return pending;

    const entry = getLoadableExtensions()?.find((candidate) => candidate.id === id);
    if (!entry || !failedImports.delete(id)) return Promise.resolve();

    clearExtensionError(id, 'boot');
    setExtensionState({ id, status: 'loading' });
    const retry = bootExtension(entry).finally(() => retries.delete(id));
    retries.set(id, retry);

    return retry;
}
