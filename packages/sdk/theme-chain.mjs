/**
 * Finds the themes a theme package extends, for the build preset and the test aliases. The chain
 * starts at "theme.extends" in the package's extension.json and follows each parent's own manifest.
 * Each parent is looked up, in this order:
 *
 *   1. <extensions>/<id>, when `extensions` names the panel's extensions directory (the copy the
 *      panel installed, which the scaffold points at);
 *   2. node_modules/<id> of the package, for a parent installed as a dependency.
 */
import { existsSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const CLASSIC = 'classic';

function manifest(directory) {
    const file = resolve(directory, 'extension.json');

    return existsSync(file) ? JSON.parse(readFileSync(file, 'utf8')) : null;
}

/** The nearest directory at or above `from` with an extension.json, else null. */
export function packageRoot(from) {
    for (let directory = from; ; directory = dirname(directory)) {
        if (existsSync(resolve(directory, 'extension.json'))) return directory;
        if (dirname(directory) === directory) return null;
    }
}

/**
 * The themes the package at `root` extends, oldest first and without classic, each as
 * `{ id, directory, manifest }`. Throws when a parent is in none of the places above.
 */
export function themeParents({ root, extensions }) {
    const parents = [];
    let child = manifest(root);
    const seen = new Set([child?.id]);

    while (child?.type === 'theme' && (child.theme?.extends ?? CLASSIC) !== CLASSIC) {
        const id = child.theme.extends;
        if (seen.has(id)) throw new Error(`Theme "${child.id}" extends itself through "${id}".`);
        seen.add(id);

        const candidates = [extensions && resolve(root, extensions, id), resolve(root, 'node_modules', id)].filter(Boolean);
        const directory = candidates.find((candidate) => {
            const found = manifest(candidate);
            return found?.id === id && found.type === 'theme';
        });
        if (!directory) {
            throw new Error(
                `Theme "${child.id}" extends "${id}", which is not in ${candidates.join(' or ')}. ` +
                    'Install it in the panel and pass its extensions directory as `extensions`, or add it to the dependencies.'
            );
        }

        child = manifest(directory);
        parents.unshift({ id, directory, manifest: child });
    }

    return parents;
}
