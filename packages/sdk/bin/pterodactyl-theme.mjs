#!/usr/bin/env node
// Theme author tooling: list the panel's views, eject classic's source for one or for a primitive's skin.
import { existsSync, mkdirSync, readFileSync, realpathSync, statSync, writeFileSync } from 'node:fs';
import { basename, dirname, extname, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const sdk = dirname(dirname(fileURLToPath(import.meta.url)));
const { version } = JSON.parse(readFileSync(resolve(sdk, 'package.json'), 'utf8'));
// Packed, classic ships beside the bin; in the panel repository it is read from its source.
const repositoryClassic = resolve(sdk, '../../resources/scripts/themes/classic');
const classicRoot = existsSync(repositoryClassic) ? repositoryClassic : resolve(sdk, 'classic');
const suffixes = ['', '.tsx', '.ts', '/index.tsx', '/index.ts'];

const usage = `Usage: pterodactyl-theme <command>

  list [--kind layout|page|block] [--json]
      Every view with its kind, context and fixtures; "eject" marks views classic has source for.
  eject <view> [--out src/views] [--entry src] [--force]
      Copies classic's source for a view into your theme and prints the line to add to "views".
  skins [primitive] [--out src/skins] [--entry src] [--force]
      Lists each primitive with classic's file that skins it, or copies that file into your theme.`;

class CliError extends Error {}

function parseArgs(argv) {
    const positional = [];
    const flags = {};

    for (let index = 0; index < argv.length; index++) {
        const arg = argv[index];

        if (!arg.startsWith('--')) {
            positional.push(arg);
            continue;
        }

        const [name, inline] = arg.slice(2).split('=', 2);
        const next = argv[index + 1];

        if (inline !== undefined) flags[name] = inline;
        else if (['out', 'entry', 'kind'].includes(name) && next !== undefined) flags[name] = argv[++index];
        else flags[name] = true;
    }

    return { positional, flags };
}

function catalog() {
    return JSON.parse(readFileSync(resolve(sdk, 'views.json'), 'utf8'));
}

function suggest(name, names) {
    const terms = name.toLowerCase().split(/[.\s]+/).filter(Boolean);
    const scored = names
        .map((candidate) => ({ candidate, score: terms.filter((term) => candidate.toLowerCase().includes(term)).length }))
        .filter(({ score }) => score > 0)
        .sort((a, b) => b.score - a.score || a.candidate.localeCompare(b.candidate));

    return scored.slice(0, 3).map(({ candidate }) => candidate);
}

function list(flags, out) {
    const views = Object.entries(catalog()).filter(([, view]) => !flags.kind || view.kind === flags.kind);

    if (flags.json) {
        out(JSON.stringify(Object.fromEntries(views), null, 4));

        return;
    }

    const rows = views.map(([name, view]) => [
        name,
        view.kind,
        view.context,
        view.fixtures.length ? view.fixtures.join(', ') : '-',
        view.classic ? 'eject' : '',
    ]);
    const header = ['VIEW', 'KIND', 'CONTEXT', 'FIXTURES', ''];
    const widths = header.map((title, column) => Math.max(title.length, ...rows.map((row) => row[column].length)));
    const line = (row) => row.map((cell, column) => cell.padEnd(widths[column])).join('  ').trimEnd();

    out([line(header), ...rows.map(line)].join('\n'));
    out(`\n${views.length} views. Eject one with: pterodactyl-theme eject <view>`);
}

function resolveModule(from, specifier) {
    const base = resolve(dirname(from), specifier);

    return suffixes.map((suffix) => base + suffix).find((path) => existsSync(path) && statSync(path).isFile());
}

/** Relative imports and re-exports of a file, which an ejected view needs beside it. */
function relativeImports(source) {
    const pattern = /(?:from\s+|import\s*\(\s*|import\s+)(['"])(\.{1,2}\/[^'"]+)\1/g;

    return [...source.matchAll(pattern)].map((match) => match[2]);
}

/** `@/sdk` and `@/sdk/theme`, as classic imports them, to the published package names. */
export function rewriteImports(source) {
    return source.replaceAll(/(['"])@\/sdk(\/[\w-]+)?\1/g, '$1@pterodactyl/sdk$2$1');
}

function identifier(file, exportName) {
    if (exportName !== 'default') return exportName;

    const name = basename(file, extname(file));
    const stem = name === 'index' ? basename(dirname(file)) : name;

    return stem.replaceAll(/(?:^|[-_.\s]+)(\w)/g, (_, letter) => letter.toUpperCase());
}

/**
 * Copies `main`, a classic file, with the classic files it imports, into `outDir` at their paths under
 * `base` of classic. A file an earlier copy wrote is kept; `main` is replaced only with `force`.
 * Returns the files written and the line that imports `binding` from `main` in `entryDir`.
 */
function copyClassic(main, { root, base, outDir, entryDir, force, binding }) {
    const target = (file) => resolve(outDir, relative(resolve(root, base), file));

    if (existsSync(target(main)) && !force) {
        throw new CliError(`${relative(process.cwd(), target(main))} already exists. Pass --force to replace it.`);
    }

    const queue = [main];
    const written = [];

    while (queue.length) {
        const file = queue.shift();
        const destination = target(file);

        // A file an earlier copy wrote may have been edited since; keep it.
        if (written.includes(destination) || (file !== main && existsSync(destination))) continue;

        const source = readFileSync(file, 'utf8');

        for (const specifier of relativeImports(source)) {
            const dependency = resolveModule(file, specifier);

            if (!dependency?.startsWith(root + sep)) {
                throw new CliError(`${relative(root, file)} imports ${specifier}, which classic does not ship.`);
            }

            queue.push(dependency);
        }

        const origin = relative(root, file).split(sep).join('/');

        mkdirSync(dirname(destination), { recursive: true });
        writeFileSync(
            destination,
            `// Ejected from the classic theme of @pterodactyl/sdk ${version} (${origin}).\n${rewriteImports(source)}`
        );
        written.push(destination);
    }

    const path = relative(entryDir, target(main)).split(sep).join('/').replace(/\.tsx?$/, '');

    return { written, importLine: `import ${binding} from '${path.startsWith('.') ? path : `./${path}`}';` };
}

/**
 * Copies classic's source for a view, with the files it imports from classic, into `outDir` and
 * returns what was written and the lines to add to the theme entry in `entryDir`.
 */
export function ejectView(name, { views = catalog(), root = classicRoot, outDir, entryDir, force = false }) {
    const view = views[name];

    if (!view) {
        const close = suggest(name, Object.keys(views));

        throw new CliError(
            `There is no view named "${name}".${close.length ? ` Did you mean ${close.join(', ')}?` : ''}\n` +
                'Run pterodactyl-theme list to see every view.'
        );
    }

    if (!view.classic) {
        throw new CliError(
            `Classic has no source for "${name}" yet; it renders the panel's own component.\n` +
                `Wrap it instead: '${name}': ({ model, Default }) => <Default />`
        );
    }

    const main = resolve(root, view.classic.file);
    const local = identifier(main, view.classic.export);
    const binding = view.classic.export === 'default' ? local : `{ ${local} }`;
    const copied = copyClassic(main, { root, base: 'views', outDir, entryDir, force, binding });

    return { ...copied, viewsLine: `views: { '${name}': ${local} },` };
}

function eject(positional, flags, out) {
    const [name] = positional;

    if (!name) throw new CliError('Name the view to eject, e.g. pterodactyl-theme eject dashboard.serverCard.');

    const { written, importLine, viewsLine } = ejectView(name, {
        outDir: resolve(String(flags.out ?? 'src/views')),
        entryDir: resolve(String(flags.entry ?? 'src')),
        force: !!flags.force,
    });

    out(`Ejected ${name}:`);
    for (const file of written) out(`  ${relative(process.cwd(), file)}`);
    out(`\nAdd it to your theme entry:\n\n  ${importLine}\n\n  ${viewsLine}`);
}

/** The file and export of classic's skin of each primitive, by primitive name, as `skins/index.ts` maps them. */
export function classicSkins(root = classicRoot) {
    const index = resolve(root, 'skins/index.ts');

    if (!existsSync(index)) return new Map();

    const source = readFileSync(index, 'utf8');
    const imports = new Map();

    for (const [, names, specifier] of source.matchAll(/import\s*\{([^}]*)\}\s*from\s*'(\.{1,2}\/[^']+)'/g)) {
        for (const binding of names.split(',').map((name) => name.trim()).filter(Boolean)) {
            const [imported, local = imported] = binding.split(/\s+as\s+/);

            imports.set(local, { file: resolveModule(index, specifier), exportName: imported });
        }
    }

    return new Map(
        [...source.matchAll(/^\s*([A-Z]\w*):\s*(\w+),?\s*$/gm)]
            .filter(([, , local]) => imports.has(local))
            .map(([, primitive, local]) => [primitive, imports.get(local)])
    );
}

function skins(positional, flags, out) {
    const primitives = classicSkins();
    const [name] = positional;
    const shown = (file) => relative(classicRoot, file).split(sep).join('/');

    if (!name) {
        const width = Math.max(0, ...[...primitives.keys()].map((primitive) => primitive.length));

        out(
            primitives.size
                ? [...primitives].map(([primitive, { file }]) => `${primitive.padEnd(width)}  ${shown(file)}`).join('\n')
                : 'Classic defines no skins yet.'
        );

        return;
    }

    const match = [...primitives.keys()].find((primitive) => primitive.toLowerCase() === name.toLowerCase());

    if (!match) {
        throw new CliError(
            `Classic has no skin for "${name}".` + (primitives.size ? ` It skins ${[...primitives.keys()].join(', ')}.` : '')
        );
    }

    const { file, exportName } = primitives.get(match);
    const { written, importLine } = copyClassic(file, {
        root: classicRoot,
        base: 'skins',
        outDir: resolve(String(flags.out ?? 'src/skins')),
        entryDir: resolve(String(flags.entry ?? 'src')),
        force: !!flags.force,
        binding: `{ ${exportName} }`,
    });

    out(`Copied ${shown(file)}, which skins ${match}:`);
    for (const copied of written) out(`  ${relative(process.cwd(), copied)}`);
    out(`\nAdd it to your theme entry:\n\n  ${importLine}\n\n  ui: { ${match}: ${exportName} },`);
}

export function run(argv, out = console.log) {
    const [command, ...rest] = argv;
    const { positional, flags } = parseArgs(rest);

    switch (command) {
        case 'list':
            return list(flags, out);
        case 'eject':
            return eject(positional, flags, out);
        case 'skins':
            return skins(positional, flags, out);
        case undefined:
        case 'help':
        case '--help':
            return out(usage);
        default:
            throw new CliError(`Unknown command "${command}".\n\n${usage}`);
    }
}

// npx runs the bin through a symlink in node_modules/.bin.
if (process.argv[1] && realpathSync(process.argv[1]) === realpathSync(fileURLToPath(import.meta.url))) {
    try {
        run(process.argv.slice(2));
    } catch (error) {
        if (!(error instanceof CliError)) throw error;
        console.error(error.message);
        process.exitCode = 1;
    }
}
