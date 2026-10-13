import ts from 'typescript';
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, extname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

// Fills the generated parts of the SDK docs: the view catalogue (views.json and the published view models)
// and the UI primitive reference in THEMES.md, and every code sample in either file, read from
// packages/sdk/docs-samples where `npm run sdk:check` compiles it. `--check` fails on stale docs.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sdk = resolve(root, 'packages/sdk');
const documents = ['README.md', 'THEMES.md'];
const read = (path) => readFileSync(resolve(sdk, path), 'utf8');

const program = ts.createProgram(
    ['types/index.d.ts', 'types/theme.d.ts', 'types/testing.d.ts'].map((path) => resolve(sdk, path)),
    {
        strict: true,
        noEmit: true,
        skipLibCheck: true,
        target: ts.ScriptTarget.ES2022,
        module: ts.ModuleKind.ESNext,
        moduleResolution: ts.ModuleResolutionKind.Bundler,
        jsx: ts.JsxEmit.ReactJSX,
        types: [],
    }
);
const checker = program.getTypeChecker();

/** An interface the SDK declares, with every module augmentation merged in. */
function declared(file, name) {
    const source = program.getSourceFile(resolve(sdk, file));
    const symbol = checker.getExportsOfModule(checker.getSymbolAtLocation(source)).find((entry) => entry.name === name);

    if (!symbol) throw new Error(`${file} does not export ${name}.`);

    return checker.getDeclaredTypeOfSymbol(symbol);
}

const properties = (type) => new Map(checker.getPropertiesOfType(type).map((property) => [property.name, property]));

const opaque = declared('types/views/pages.d.ts', 'OpaqueModel');

/** The type a property is declared with, as written, or as the checker prints it. */
function typeText(property) {
    const declaration = property.valueDeclaration ?? property.declarations?.[0];

    if (declaration && ts.isPropertySignature(declaration) && declaration.type) {
        return declaration.type.getText().replace(/\s+/g, ' ');
    }

    // Pages mapped from `Record<OpaquePageName, OpaqueModel>` lose the alias.
    return checker.getTypeOfSymbol(property) === opaque
        ? 'OpaqueModel'
        : checker.typeToString(checker.getTypeOfSymbol(property));
}

/** The union a type node spells out, following type aliases, so its members keep their written order. */
function unionNode(node) {
    if (!node) return null;
    if (ts.isUnionTypeNode(node)) return node;
    if (!ts.isTypeReferenceNode(node)) return null;

    const symbol = checker.getSymbolAtLocation(node.typeName);
    const target = symbol && symbol.flags & ts.SymbolFlags.Alias ? checker.getAliasedSymbol(symbol) : symbol;

    return unionNode(target?.declarations?.find(ts.isTypeAliasDeclaration)?.type);
}

const optional = (property) => (property.flags & ts.SymbolFlags.Optional ? '?' : '');
const code = (text) => `\`${text.replaceAll('|', '\\|')}\``;
const documentation = (symbol) => ts.displayPartsToString(symbol.getDocumentationComment(checker)).replace(/\s+/g, ' ');

/** The literal values of a property's union type, such as `'sm' | 'md'`, in written order; null for any other type. */
function literals(property) {
    const declaration = property.valueDeclaration;
    const node = declaration && ts.isPropertySignature(declaration) ? unionNode(declaration.type) : null;

    if (node?.types.every((member) => ts.isLiteralTypeNode(member) && ts.isStringLiteral(member.literal))) {
        return node.types.map((member) => member.literal.text);
    }

    const type = checker.getTypeOfSymbol(property);
    const members = (type.isUnion() ? type.types : [type]).filter(
        (member) => !(member.flags & (ts.TypeFlags.Undefined | ts.TypeFlags.Null))
    );

    if (members.every((member) => member.flags & ts.TypeFlags.BooleanLiteral)) return 'boolean';
    if (members.every((member) => member.isStringLiteral() || member.isNumberLiteral())) {
        return members.map((member) => String(member.value));
    }

    return null;
}

function viewCatalogue() {
    const views = JSON.parse(read('views.json'));
    const models = properties(declared('types/views/registry.d.ts', 'ViewModels'));
    const inputs = properties(declared('types/views/registry.d.ts', 'ViewInputs'));
    const parts = properties(declared('types/views/registry.d.ts', 'ViewPartNames'));
    const model = (name) => {
        const property = models.get(name);

        if (!property) throw new Error(`views.json names ${name}, which has no published model.`);

        return typeText(property);
    };
    const row = (withEngine) => ([name, view]) => {
        const input = inputs.get(name);
        const inputText = input
            ? [...properties(checker.getTypeOfSymbol(input)).values()]
                  .map((property) => code(`${property.name}${optional(property)}: ${typeText(property)}`))
                  .join(', ')
            : '';
        const partNames = parts.get(name) ? literals(parts.get(name)) : null;
        const area = view.areaSlots ? `<br>where it draws an area's navigation: ${view.areaSlots.map(code).join(' ')}` : '';

        return [
            code(name),
            view.context,
            code(model(name)),
            [inputText, partNames ? `parts: ${partNames.map(code).join(', ')}` : ''].filter(Boolean).join('<br>'),
            view.slots.map(code).join(' ') + area,
            ...(withEngine ? [(view.engineSlots ?? []).map(code).join(' ')] : []),
            view.fixtures.join(', '),
        ];
    };
    const table = (title, entries, withEngine = false) =>
        [
            `#### ${title}`,
            '',
            `| View | Context | Model | Inputs and parts | Slots |${withEngine ? ' Engine slots |' : ''} Fixtures |`,
            `| --- | --- | --- | --- | --- |${withEngine ? ' --- |' : ''} --- |`,
            ...entries.map(row(withEngine)).map((cells) => `| ${cells.join(' | ')} |`),
        ].join('\n');
    const all = Object.entries(views);
    const opaquePages = all.filter(([name, view]) => view.kind === 'page' && model(name) === 'OpaqueModel');

    return [
        table(
            'Layouts',
            all.filter(([, view]) => view.kind === 'layout')
        ),
        table(
            'Pages',
            all.filter(([name, view]) => view.kind === 'page' && model(name) !== 'OpaqueModel'),
            true
        ),
        table(
            'Blocks',
            all.filter(([, view]) => view.kind === 'block')
        ),
        [
            '#### Opaque pages',
            '',
            "These pages still render the panel's own containers: their model is `OpaqueModel` (empty), they have no",
            'fixtures, and `Default` renders the container. Wrap them, or place blocks around them.',
            '',
            '| View | Slots the engine renders around it |',
            '| --- | --- |',
            ...opaquePages.map(([name, view]) => `| ${code(name)} | ${(view.engineSlots ?? []).map(code).join(' ')} |`),
        ].join('\n'),
    ].join('\n\n');
}

const kebab = (name) => name.replaceAll(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);

function primitiveReference() {
    const primitives = properties(declared('types/themes/primitives.d.ts', 'UiPrimitives'));
    const rows = [...primitives.values()].map((primitive) => {
        const shape = properties(checker.getTypeOfSymbol(primitive));
        const parts = literals(shape.get('parts'));
        const state = [...properties(checker.getTypeOfSymbol(shape.get('state'))).values()].map((property) => {
            const values = literals(property);
            const attribute = code(`data-${kebab(property.name)}`);

            return values === 'boolean' || values === null ? attribute : `${attribute}: ${values.join(', ')}`;
        });
        const notes = [documentation(primitive), documentation(shape.get('state'))].filter(Boolean).join(' ');

        return [
            primitive.name,
            parts.map(code).join(', '),
            state.join('; ') || 'none',
            shape.has('props') ? 'yes' : 'no',
            notes,
        ];
    });

    return [
        '| Primitive | Parts | State on `root` | Replaceable | Notes |',
        '| --- | --- | --- | --- | --- |',
        ...rows.map((cells) => `| ${cells.join(' | ')} |`),
    ].join('\n');
}

const languages = { '.tsx': 'tsx', '.ts': 'ts', '.css': 'css', '.json': 'json', '.mjs': 'js', '.js': 'js' };

const marker = /^\s*(?:\/\/|\/\*|\{\/\*)\s*#(?:end)?region\b.*$/;

/** The lines of each `#region name` … `#endregion` block in a file, dedented and joined by a blank line. */
function regions(path, lines, name) {
    const blocks = [];

    lines.forEach((line, start) => {
        if (!new RegExp(`#region ${name}\\b`).test(line)) return;

        const end = lines.findIndex((candidate, index) => index > start && /#endregion/.test(candidate));
        const block = lines.slice(start + 1, end);
        const indent = Math.min(...block.filter((entry) => entry.trim()).map((entry) => /^\s*/.exec(entry)[0].length));

        blocks.push(block.map((entry) => entry.slice(indent)).join('\n'));
    });

    if (!blocks.length) throw new Error(`docs-samples/${path} has no region "${name}".`);

    return blocks.join('\n\n').split('\n');
}

/** A sample file, or its regions of one name, without the region markers. */
function sample(reference) {
    const [path, region] = reference.split('#');
    const lines = read(`docs-samples/${path}`).split('\n');
    const body = (region ? regions(path, lines, region) : lines)
        .filter((line) => !marker.test(line))
        .join('\n')
        .trim();

    return `\`\`\`${languages[extname(path)] ?? ''}\n${body}\n\`\`\``;
}

function fill(readme) {
    const generated = { views: viewCatalogue, primitives: primitiveReference };
    const output = readme.replace(
        /(<!-- generated:(\w+) -->)[\s\S]*?(<!-- \/generated:\2 -->)/g,
        (_, open, name, close) => {
            if (!generated[name]) throw new Error(`Unknown generated section "${name}".`);

            return `${open}\n${generated[name]()}\n${close}`;
        }
    );

    return output.replace(
        /(<!-- sample: (\S+) -->)[\s\S]*?(<!-- \/sample -->)/g,
        (_, open, reference, close) => `${open}\n${sample(reference)}\n${close}`
    );
}

const stale = documents.filter((document) => {
    const path = resolve(sdk, document);
    const text = readFileSync(path, 'utf8');
    const output = fill(text);

    if (output !== text && !process.argv.includes('--check')) {
        writeFileSync(path, output);
    }

    return output !== text;
});

if (stale.length && process.argv.includes('--check')) {
    console.error(`${stale.map((document) => `packages/sdk/${document}`).join(' and ')} stale. Run npm run sdk:generate.`);
    process.exit(1);
}

console.log(`SDK: docs samples and references ${stale.length ? `regenerated in ${stale.join(', ')}` : 'current'}`);
