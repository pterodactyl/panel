import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import ts from 'typescript';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const argument = (name, fallback) => {
    const index = process.argv.indexOf(name);

    return index === -1 ? fallback : resolve(process.argv[index + 1]);
};
const source = argument('--src', resolve(root, 'resources/scripts'));
const output = argument('--out', resolve(root, 'resources/lang/en.json'));
const ignored = /(?:\.spec\.tsx?|\.d\.ts)$|\/(?:generated|node_modules|testing-runtime)\//;
const plurals = ['one', 'other'];
const countPlaceholder = /\{\{\s*count\s*\}\}/;

const literal = (node) => (node && (ts.isStringLiteral(node) || ts.isNoSubstitutionTemplateLiteral(node)) ? node.text : null);

const property = (node, name) =>
    node && ts.isObjectLiteralExpression(node)
        ? node.properties.find((entry) => entry.name && ts.isIdentifier(entry.name) && entry.name.text === name)
        : undefined;

const extensionBindings = (file) => {
    const names = new Set();

    const visit = (node) => {
        if (
            ts.isVariableDeclaration(node) &&
            ts.isObjectBindingPattern(node.name) &&
            node.initializer &&
            ts.isCallExpression(node.initializer) &&
            node.initializer.expression.getText(file) === 'useExtensionTranslation'
        ) {
            node.name.elements.forEach((element) => names.add(element.name.getText(file)));
        }

        ts.forEachChild(node, visit);
    };

    visit(file);

    return names;
};

const isTranslateCall = (node, file, skipped) => {
    const callee = node.expression;

    if (ts.isIdentifier(callee)) {
        return (callee.text === 't' && !skipped.has('t')) || callee.text === 'translatable';
    }

    return ts.isPropertyAccessExpression(callee) && callee.name.text === 't' && !skipped.has(callee.expression.getText(file));
};

const translateElementKey = (node) => {
    const attributes = Object.fromEntries(
        node.attributes.properties
            .filter((attribute) => ts.isJsxAttribute(attribute) && attribute.initializer)
            .map((attribute) => [attribute.name.getText(), attribute.initializer]),
    );
    const namespace = attributes.ns ? literal(attributes.ns) : 'translation';

    return namespace === 'translation' ? literal(attributes.i18nKey) : null;
};

function collect(filename, keys) {
    const file = ts.createSourceFile(filename, readFileSync(filename, 'utf8'), ts.ScriptTarget.Latest, true);
    const skipped = extensionBindings(file);

    const visit = (node) => {
        if (ts.isCallExpression(node) && isTranslateCall(node, file, skipped)) {
            const key = literal(node.arguments[0]);
            const options = node.arguments[1];

            if (key !== null && !property(options, 'ns')) {
                keys.set(key, keys.get(key) || !!property(options, 'count') || countPlaceholder.test(key));
            }
        }

        if ((ts.isJsxSelfClosingElement(node) || ts.isJsxOpeningElement(node)) && node.tagName.getText(file) === 'Translate') {
            const key = translateElementKey(node);

            if (key !== null) {
                keys.set(key, keys.get(key) || countPlaceholder.test(key));
            }
        }

        ts.forEachChild(node, visit);
    };

    visit(file);
}

const keys = new Map();

for (const entry of readdirSync(source, { recursive: true })) {
    const filename = resolve(source, entry);

    if (/\.tsx?$/.test(filename) && !ignored.test(filename)) {
        collect(filename, keys);
    }
}

const existing = existsSync(output) ? JSON.parse(readFileSync(output, 'utf8')) : {};
const catalogue = {};

for (const [key, plural] of [...keys].sort(([left], [right]) => (left < right ? -1 : left > right ? 1 : 0))) {
    if (plural) {
        plurals.forEach((form) => (catalogue[`${key}_${form}`] = existing[`${key}_${form}`] ?? key));
    } else {
        catalogue[key] = key;
    }
}

const expected = `${JSON.stringify(catalogue, null, 4)}\n`;

if (process.argv.includes('--check')) {
    if (!existsSync(output) || readFileSync(output, 'utf8') !== expected) {
        console.error(`${relative(root, output)} is out of date. Run npm run lang:extract.`);
        process.exit(1);
    }
} else {
    writeFileSync(output, expected);
}
