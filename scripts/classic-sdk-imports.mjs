import ts from 'typescript';
import { existsSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';

// Classic imports its primitives from `@/sdk`, one module re-exporting the whole SDK, so a chunk importing
// anything from it loads every primitive. The build points each of classic's imports at the module that
// declares it, so a page's chunk carries only what it uses.
const suffixes = ['', '.ts', '.tsx', '/index.ts', '/index.tsx'];

const exported = (node) =>
    ts.canHaveModifiers(node) && (ts.getModifiers(node) ?? []).some((modifier) => modifier.kind === ts.SyntaxKind.ExportKeyword);
const exportedDefault = (node) =>
    exported(node) && (ts.getModifiers(node) ?? []).some((modifier) => modifier.kind === ts.SyntaxKind.DefaultKeyword);

/** The names a declaration binds, `export const { a, b } = ...` included. */
function boundNames(name, names) {
    if (ts.isIdentifier(name)) {
        names.add(name.text);
    } else {
        for (const element of name.elements) {
            if (!ts.isOmittedExpression(element)) boundNames(element.name, names);
        }
    }
}

/** The value names a module declares and exports itself. */
function localExports(source) {
    const names = new Set();

    for (const statement of source.statements) {
        if (ts.isExportAssignment(statement) || exportedDefault(statement)) {
            names.add('default');
        } else if (!exported(statement)) {
            continue;
        } else if ((ts.isFunctionDeclaration(statement) || ts.isClassDeclaration(statement)) && statement.name) {
            names.add(statement.name.text);
        } else if (ts.isEnumDeclaration(statement)) {
            names.add(statement.name.text);
        } else if (ts.isVariableStatement(statement)) {
            for (const declaration of statement.declarationList.declarations) boundNames(declaration.name, names);
        }
    }

    return names;
}

/** `resolve(name)` gives the file and export declaring what `@/sdk` exports as `name`, or undefined. */
export function sdkExportSources(scripts) {
    const sources = new Map();
    const parse = (file) => {
        if (!sources.has(file)) {
            sources.set(file, ts.createSourceFile(file, readFileSync(file, 'utf8'), ts.ScriptTarget.Latest, true));
        }

        return sources.get(file);
    };
    const resolveModule = (from, specifier) => {
        const base = specifier.startsWith('@/') ? resolve(scripts, specifier.slice(2)) : resolve(dirname(from), specifier);
        const file = suffixes.map((suffix) => base + suffix).find((path) => existsSync(path) && statSync(path).isFile());

        if (!file) throw new Error(`Cannot resolve ${specifier} from ${from}`);

        return file;
    };

    // Explicit exports win over `export *`, as they do in the module itself.
    function declaration(file, name) {
        const source = parse(file);

        if (localExports(source).has(name)) return { file, name };

        const stars = [];

        for (const statement of source.statements) {
            if (!ts.isExportDeclaration(statement) || statement.isTypeOnly) continue;

            if (!statement.exportClause) {
                stars.push(statement.moduleSpecifier.text);
                continue;
            }

            if (!ts.isNamedExports(statement.exportClause)) continue;

            for (const element of statement.exportClause.elements) {
                if (element.isTypeOnly || element.name.text !== name) continue;

                const specifier = statement.moduleSpecifier?.text;
                const local = (element.propertyName ?? element.name).text;

                // An imported binding exported again stops here: importing it from this module is still right.
                if (!specifier) return { file, name };

                // A package's export is imported from the package.
                return specifier.startsWith('.') || specifier.startsWith('@/')
                    ? declaration(resolveModule(file, specifier), local)
                    : { file: specifier, name: local };
            }
        }

        for (const specifier of stars.filter((star) => star.startsWith('.') || star.startsWith('@/'))) {
            const found = declaration(resolveModule(file, specifier), name);

            if (found) return found;
        }

        return undefined;
    }

    return { resolve: (name) => declaration(resolve(scripts, 'sdk/index.ts'), name) };
}

/**
 * Rewrites the `@/sdk` imports of a module to import each value from the module declaring it; type imports
 * stay on `@/sdk`. Returns null when the module imports nothing from it.
 */
export function rewriteSdkImports(code, file, sources) {
    if (!code.includes("from '@/sdk'")) return null;

    const source = ts.createSourceFile(file, code, ts.ScriptTarget.Latest, true);
    let output = code;

    for (const statement of [...source.statements].reverse()) {
        if (!ts.isImportDeclaration(statement) || statement.moduleSpecifier.text !== '@/sdk') continue;

        const clause = statement.importClause;
        const bindings = clause?.namedBindings;

        if (!clause || clause.isTypeOnly || clause.name || !bindings || !ts.isNamedImports(bindings)) continue;

        const types = bindings.elements.filter((element) => element.isTypeOnly);
        const imports = bindings.elements
            .filter((element) => !element.isTypeOnly)
            .map((element) => {
                const imported = (element.propertyName ?? element.name).text;
                const declared = sources.resolve(imported);

                if (!declared) throw new Error(`@/sdk does not export ${imported} (imported by ${file}).`);

                return `import { ${declared.name} as ${element.name.text} } from ${JSON.stringify(declared.file)};`;
            });

        if (types.length) {
            const names = types.map((element) => element.getText(source).replace(/^type\s+/, ''));

            imports.push(`import type { ${names.join(', ')} } from '@/sdk';`);
        }

        const start = statement.getStart(source);
        // The import keeps its lines, so the source map of the lines below stays right.
        const lines = code.slice(start, statement.end).split('\n').length - 1;

        output = output.slice(0, start) + imports.join(' ') + '\n'.repeat(lines) + output.slice(statement.end);
    }

    return output;
}

/** The build plugin applying `rewriteSdkImports` to classic's modules. */
export function classicSdkImports(scripts) {
    const classic = join(scripts, 'themes/classic/');
    let sources;

    return {
        name: 'pterodactyl-classic-sdk-imports',
        apply: 'build',
        enforce: 'pre',
        buildStart() {
            sources = sdkExportSources(scripts);
        },
        transform(code, id) {
            if (!id.startsWith(classic)) return null;

            const output = rewriteSdkImports(code, id, sources);

            return output === null ? null : { code: output, map: null };
        },
    };
}
