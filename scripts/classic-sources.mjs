import ts from 'typescript';
import { existsSync, readFileSync, statSync } from 'node:fs';
import { dirname, relative, resolve, sep } from 'node:path';

// Maps each view classic implements to the file and export of its renderer, read from the
// classic theme's `views` map, so `pterodactyl-theme eject` copies the right file.
const suffixes = ['', '.tsx', '.ts', '/index.tsx', '/index.ts'];

function resolveModule(from, specifier) {
    const base = resolve(dirname(from), specifier);
    const file = suffixes.map((suffix) => base + suffix).find((path) => existsSync(path) && statSync(path).isFile());

    if (!file) throw new Error(`Cannot resolve ${specifier} from ${from}`);

    return file;
}

const parse = (file) => ts.createSourceFile(file, readFileSync(file, 'utf8'), ts.ScriptTarget.Latest, true);

/** Follows re-exports (`export { default as X } from './X'`) to the file that declares an export. */
function declaringFile(file, exportName) {
    for (const statement of parse(file).statements) {
        if (!ts.isExportDeclaration(statement) || !statement.moduleSpecifier || !statement.exportClause) continue;
        if (!ts.isNamedExports(statement.exportClause)) continue;
        const element = statement.exportClause.elements.find((candidate) => candidate.name.text === exportName);

        if (element) {
            return declaringFile(resolveModule(file, statement.moduleSpecifier.text), (element.propertyName ?? element.name).text);
        }
    }

    return { file, exportName };
}

/** `() => import('./layout/AppLayout')` written inline as the importer. */
function inlineImport(node) {
    if (!node || !ts.isArrowFunction(node) || !ts.isCallExpression(node.body)) return undefined;
    const [argument] = node.body.arguments;

    return node.body.expression.kind === ts.SyntaxKind.ImportKeyword && argument && ts.isStringLiteral(argument)
        ? { specifier: argument.text, lazy: true }
        : undefined;
}

export function classicViewSources(classicRoot) {
    const index = resolve(classicRoot, 'views/index.ts');
    const source = parse(index);
    const modules = new Map();
    const views = new Map();

    for (const statement of source.statements) {
        if (ts.isImportDeclaration(statement) && statement.importClause) {
            const specifier = statement.moduleSpecifier.text;
            const { name, namedBindings } = statement.importClause;

            if (name) modules.set(name.text, { specifier, exportName: 'default' });
            if (namedBindings && ts.isNamedImports(namedBindings)) {
                for (const element of namedBindings.elements) {
                    modules.set(element.name.text, { specifier, exportName: (element.propertyName ?? element.name).text });
                }
            }
        }

        if (!ts.isVariableStatement(statement)) continue;

        for (const declaration of statement.declarationList.declarations) {
            const initializer = declaration.initializer;

            // const layout = () => import('./layout/AppLayout');
            if (initializer && ts.isArrowFunction(initializer) && ts.isCallExpression(initializer.body)) {
                const [argument] = initializer.body.arguments;

                if (initializer.body.expression.kind === ts.SyntaxKind.ImportKeyword && argument && ts.isStringLiteral(argument)) {
                    modules.set(declaration.name.getText(source), { specifier: argument.text, lazy: true });
                }
            }

            if (!initializer || !ts.isObjectLiteralExpression(initializer)) continue;

            for (const property of initializer.properties) {
                if (!ts.isPropertyAssignment(property) || !ts.isStringLiteral(property.name)) continue;
                views.set(property.name.text, property.initializer);
            }
        }
    }

    const sources = {};

    for (const [view, initializer] of views) {
        // lazyView(importer, 'Export') or a component imported by name.
        const call = ts.isCallExpression(initializer) ? initializer : null;
        const reference = call ? call.arguments[0] : initializer;
        const named = call?.arguments[1];
        const module = reference && ts.isIdentifier(reference) ? modules.get(reference.text) : inlineImport(reference);

        if (!module) throw new Error(`Cannot find the classic source of ${view} in ${index}`);

        const exportName = named && ts.isStringLiteral(named) ? named.text : (module.exportName ?? 'default');
        const declared = declaringFile(resolveModule(index, module.specifier), exportName);

        sources[view] = {
            file: relative(classicRoot, declared.file).split(sep).join('/'),
            export: declared.exportName,
        };
    }

    return sources;
}
