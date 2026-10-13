import { getBootstrapExtensions, getBootstrapThemeChain } from '@/bootstrap';
import type { ExtensionDefinition, ExtensionSetupContext } from '@/sdk';
import { isObject } from '@/lib/objects';
import {
    abortExtensionRegistryBatch,
    commitExtensionRegistryBatch,
    createExtensionRegistryBatch,
    registerScreen,
    registerSlotComponent,
    registerExtensionTableColumn,
    registerFormExtension,
    registerViewOverride,
    failExtensionLoad,
    clearExtensionError,
    setExtensionState,
    prepareExtensions,
    getLoadableExtensions,
    type ExtensionRegistryBatch,
    type SiteExtensionEntry,
} from '@/extensions/registry';
import type { ExtensionTableColumnRegistration } from './tableTypes';
import type { FormExtension } from './formTypes';
import { startExtensionDevelopmentReload } from './development';

export const EXTENSION_IMPORT_TIMEOUT_MS = 15_000;

const isThenable = <T>(value: T): value is T & PromiseLike<void> =>
    isObject(value) && 'then' in value && value.then instanceof Function;

/** Rejects when the import takes longer than an extension bundle may; the theme chain is held to the same limit. */
export const withImportTimeout = async <T>(promise: Promise<T>): Promise<T> => {
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

/**
 * Guests are sent no screens or view overrides, since neither renders on the sign-in pages, so
 * the same setup() skips those registrations there instead of failing on undeclared names.
 */
const createContext = (entry: SiteExtensionEntry, batch: ExtensionRegistryBatch): ExtensionSetupContext => ({
    meta: { id: entry.id, version: entry.version },
    config: entry.config ?? {},
    views: {
        override: (name, view) => {
            if (entry.views) {
                registerViewOverride(entry.id, name, view, batch);
            }
        },
    },
    slots: {
        register: (name, component) =>
            registerSlotComponent(entry.id, name, component as Parameters<typeof registerSlotComponent>[2], batch),
    },
    screens: {
        register: (id, component, options) => {
            if (entry.screens) {
                registerScreen(entry.id, id, component, batch, options);
            }
        },
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
    forms: {
        extend: (form, component) =>
            registerFormExtension({ extensionId: entry.id, form, component: component as FormExtension }, batch),
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

/** The bundle's definition; null for a theme without `setup`, which has nothing to register. */
const parseExtensionDefinition = <T extends object>(module: T, theme: boolean): ExtensionDefinition | null => {
    const definition = 'default' in module && isObject(module.default) ? module.default : undefined;

    if (theme && definition && !('setup' in definition)) {
        return null;
    }

    if (!definition || !('setup' in definition) || !(definition.setup instanceof Function)) {
        throw new Error('bundle has no default export with a setup() function');
    }

    return definition as ExtensionDefinition;
};

let loading: Promise<void> | undefined;
let importBundle: ModuleImporter = nativeImport;
const failedImports = new Set<string>();

async function bootExtension(entry: SiteExtensionEntry): Promise<void> {
    let module: ExtensionModule;

    try {
        module = await withImportTimeout(importBundle(entry.entry));
    } catch (cause) {
        failedImports.add(entry.id);
        failExtensionLoad(entry.id, 'boot', describeBootError(cause));

        return;
    }

    const batch = createExtensionRegistryBatch();

    try {
        const definition = parseExtensionDefinition(module, entry.theme === true);
        const setupResult = definition?.setup(createContext(entry, batch));

        if (isThenable(setupResult)) {
            void Promise.resolve(setupResult).catch(() => {});
            throw new Error('setup() must register synchronously and must not return a promise');
        }

        commitExtensionRegistryBatch(entry.id, batch);
    } catch (cause) {
        if (!batch.closed) {
            abortExtensionRegistryBatch(batch);
        }

        failExtensionLoad(entry.id, 'boot', describeBootError(cause));
    }
}

/** Call once after the core mounts. The route tree has already registered each extension's class prefix. */
export function loadExtensions(importModule: ModuleImporter = nativeImport): Promise<void> {
    if (loading) {
        return loading;
    }

    importBundle = importModule;
    const entries = prepareExtensions(getBootstrapExtensions());

    // A theme with JavaScript is an entry too; one poller per package.
    const ids = new Set(entries.map(({ id }) => id));

    startExtensionDevelopmentReload([...entries, ...getBootstrapThemeChain().filter(({ id }) => !ids.has(id))]);
    loading = Promise.all(entries.map(bootExtension)).then(() => {});

    return loading;
}

export function canRetryExtension(id: string): boolean {
    return failedImports.has(id);
}

/** Only a failed import can be retried, once per failure. */
export function retryExtension(id: string): Promise<void> {
    const entry = getLoadableExtensions()?.find((candidate) => candidate.id === id);

    if (!entry || !failedImports.delete(id)) {
        return Promise.resolve();
    }

    clearExtensionError(id, 'boot');
    setExtensionState({ id, status: 'loading' });

    return bootExtension(entry);
}
