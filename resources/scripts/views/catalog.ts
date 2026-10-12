// Every engine view: the source of the SDK's views.json, the workbench and the SDK's test runtime.
import { getViewDefinitions, loadViewImplementation, type ViewDefinition } from './registry';
import './definitions';
import './pages';

/** Loads every area's module, so each view comes with its hook and fixtures. */
export async function loadViewCatalog(): Promise<readonly ViewDefinition[]> {
    await Promise.all(getViewDefinitions().map(({ name }) => loadViewImplementation(name)));

    return getViewDefinitions();
}
